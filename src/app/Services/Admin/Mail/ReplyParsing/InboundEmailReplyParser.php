<?php

namespace App\Services\Admin\Mail\ReplyParsing;

use App\Data\Admin\Mail\ParsedInboundEmailContentData;
use App\Enums\Admin\Mail\ReplyParsingContentType;
use App\Models\Admin\Mail\EmailMessage;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Throwable;

class InboundEmailReplyParser
{
    public function __construct(
        private readonly ReplyParsingService $replyParsing,
    ) {}

    public function parse(
        EmailMessage $message
    ): ParsedInboundEmailContentData {
        $parsingEnabled = (bool) config(
            'simpledesk-mail-reply-parsing.enabled',
            true
        );

        $stripQuotedText = $parsingEnabled
            && (bool) config(
                'simpledesk-mail-reply-parsing.strip_quoted_text',
                true
            );

        [
            $sourceContent,
            $source,
            $contentType,
        ] = $this->sourceBody(
            $message
        );

        [
            $originalBody,
        ] = $this->contentToText(
            content: $sourceContent,
            contentType: $contentType,
            stripHtmlQuotedText: false,
        );

        $originalBody = $this->normalizeText(
            $originalBody
        );

        $originalLength = mb_strlen(
            $originalBody
        );

        if (! $parsingEnabled) {
            $body = $this->limitBody(
                $originalBody
            );

            return new ParsedInboundEmailContentData(
                body: $body,
                source: $source,
                quotedTextRemoved: false,
                signatureRemoved: false,
                originalLength: $originalLength,
                parsedLength: mb_strlen($body),
            );
        }

        $workingContent = $sourceContent;

        $quotedTextRemoved = false;

        if (
            $stripQuotedText
            && $contentType !== null
        ) {
            [
                $workingContent,
                $configuredRuleMatched,
            ] = $this->applyConfiguredRules(
                content: $workingContent,
                contentType: $contentType,
            );

            $quotedTextRemoved =
                $configuredRuleMatched;
        }

        [
            $body,
            $htmlQuotedTextRemoved,
        ] = $this->contentToText(
            content: $workingContent,
            contentType: $contentType,
            stripHtmlQuotedText: $stripQuotedText,
        );

        $body = $this->normalizeText(
            $body
        );

        $quotedTextRemoved =
            $quotedTextRemoved
            || $htmlQuotedTextRemoved;

        if ($stripQuotedText) {
            [
                $body,
                $plainTextQuoteRemoved,
            ] = $this->stripQuotedText(
                $body
            );

            $quotedTextRemoved =
                $quotedTextRemoved
                || $plainTextQuoteRemoved;
        }

        $signatureRemoved = false;

        if (
            (bool) config(
                'simpledesk-mail-reply-parsing.strip_signatures',
                true
            )
        ) {
            [
                $body,
                $signatureRemoved,
            ] = $this->stripSignature(
                $body
            );
        }

        $body = $this->normalizeText(
            $body
        );

        if ($body === '') {
            if (
                (bool) config(
                    'simpledesk-mail-reply-parsing.fallback_to_full_body',
                    false
                )
                && $originalBody !== ''
            ) {
                $body = $originalBody;
            } else {
                $body = (string) config(
                    'simpledesk-mail-reply-parsing.empty_body_fallback',
                    'Ответ не содержит нового текстового содержимого.'
                );
            }
        }

        $body = $this->limitBody(
            $body
        );

        return new ParsedInboundEmailContentData(
            body: $body,
            source: $source,
            quotedTextRemoved: $quotedTextRemoved,
            signatureRemoved: $signatureRemoved,
            originalLength: $originalLength,
            parsedLength: mb_strlen($body),
        );
    }

    /**
     * @return array{
     *     0: string,
     *     1: string,
     *     2: ReplyParsingContentType|null
     * }
     */
    private function sourceBody(
        EmailMessage $message
    ): array {
        $textBody = trim(
            (string) $message->text_body
        );

        $htmlBody = trim(
            (string) $message->html_body
        );

        $preferPlainText = (bool) config(
            'simpledesk-mail-reply-parsing.prefer_plain_text',
            true
        );

        if (
            $preferPlainText
            && $textBody !== ''
        ) {
            return [
                $textBody,
                'text',
                ReplyParsingContentType::PlainText,
            ];
        }

        if ($htmlBody !== '') {
            return [
                $htmlBody,
                'html',
                ReplyParsingContentType::Html,
            ];
        }

        if ($textBody !== '') {
            return [
                $textBody,
                'text',
                ReplyParsingContentType::PlainText,
            ];
        }

        return [
            '',
            'empty',
            null,
        ];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function contentToText(
        string $content,
        ?ReplyParsingContentType $contentType,
        bool $stripHtmlQuotedText,
    ): array {
        if (
            $contentType
            === ReplyParsingContentType::Html
        ) {
            return $this->htmlToText(
                html: $content,
                stripQuotedText: $stripHtmlQuotedText,
            );
        }

        return [
            $this->normalizeText(
                $content
            ),
            false,
        ];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function applyConfiguredRules(
        string $content,
        ReplyParsingContentType $contentType,
    ): array {
        if ($content === '') {
            return [
                '',
                false,
            ];
        }

        try {
            $result = $this->replyParsing->parse(
                content: $content,
                contentType: $contentType,
            );

            return [
                $result->parsedContent,
                $result->matched,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                $content,
                false,
            ];
        }
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function stripQuotedText(
        string $body
    ): array {
        if ($body === '') {
            return [
                '',
                false,
            ];
        }

        $lines = explode(
            "\n",
            $body
        );

        foreach ($lines as $index => $line) {
            if (
                $this->isQuoteSeparator(
                    line: $line,
                    lines: $lines,
                    index: $index,
                )
            ) {
                $result = implode(
                    "\n",
                    array_slice(
                        $lines,
                        0,
                        $index
                    )
                );

                return [
                    $this->normalizeText(
                        $result
                    ),
                    true,
                ];
            }
        }

        $quotedBlockIndex =
            $this->trailingQuotedBlockIndex(
                $lines
            );

        if ($quotedBlockIndex !== null) {
            $result = implode(
                "\n",
                array_slice(
                    $lines,
                    0,
                    $quotedBlockIndex
                )
            );

            return [
                $this->normalizeText(
                    $result
                ),
                true,
            ];
        }

        return [
            $body,
            false,
        ];
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function isQuoteSeparator(
        string $line,
        array $lines,
        int $index,
    ): bool {
        $trimmed = trim($line);

        if ($trimmed === '') {
            return false;
        }

        foreach (
            config(
                'simpledesk-mail-reply-parsing.custom_separators',
                []
            ) as $separator
        ) {
            if (
                is_string($separator)
                && $separator !== ''
                && $trimmed === trim($separator)
            ) {
                return true;
            }
        }

        if (
            preg_match(
                '/^-{2,}\s*(?:'
                .'original message'
                .'|forwarded message'
                .'|исходное сообщение'
                .'|пересылаемое сообщение'
                .')\s*-{2,}$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^(?:'
                .'begin forwarded message'
                .'|начало переадресованного сообщения'
                .')\s*:?$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^on\s+.{1,800}\s+wrote\s*:\s*$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^le\s+.{1,800}\s+a\s+écrit\s*:\s*$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^am\s+.{1,800}\s+schrieb\s+.{0,300}:\s*$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^el\s+.{1,800}\s+escribió\s*:\s*$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            preg_match(
                '/^.{1,800}(?:'
                .'пользователь\s+.{1,300}\s+написал(?:а)?'
                .'|написал(?:а)?'
                .')\s*:\s*$/iu',
                $trimmed
            ) === 1
        ) {
            return true;
        }

        if (
            $this->isOutlookHeaderBlock(
                lines: $lines,
                index: $index,
            )
        ) {
            return true;
        }

        if (
            preg_match(
                '/^_{8,}$/u',
                $trimmed
            ) === 1
            && $this->containsHeaderBlockAhead(
                lines: $lines,
                index: $index + 1,
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function isOutlookHeaderBlock(
        array $lines,
        int $index,
    ): bool {
        $line = trim(
            $lines[$index] ?? ''
        );

        if (
            preg_match(
                '/^(?:from|от)\s*:/iu',
                $line
            ) !== 1
        ) {
            return false;
        }

        return $this->containsHeaderBlockAhead(
            lines: $lines,
            index: $index,
        );
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function containsHeaderBlockAhead(
        array $lines,
        int $index,
    ): bool {
        $matchedHeaders = [];

        $lastIndex = min(
            count($lines) - 1,
            $index + 8
        );

        for (
            $current = $index;
            $current <= $lastIndex;
            $current++
        ) {
            $line = trim(
                $lines[$current] ?? ''
            );

            if (
                preg_match(
                    '/^(from|от)\s*:/iu',
                    $line
                ) === 1
            ) {
                $matchedHeaders['from'] =
                    true;
            }

            if (
                preg_match(
                    '/^(sent|date|отправлено|дата)\s*:/iu',
                    $line
                ) === 1
            ) {
                $matchedHeaders['date'] =
                    true;
            }

            if (
                preg_match(
                    '/^(to|кому)\s*:/iu',
                    $line
                ) === 1
            ) {
                $matchedHeaders['to'] =
                    true;
            }

            if (
                preg_match(
                    '/^(subject|тема)\s*:/iu',
                    $line
                ) === 1
            ) {
                $matchedHeaders['subject'] =
                    true;
            }
        }

        return isset(
                $matchedHeaders['from']
            ) && count($matchedHeaders) >= 3;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function trailingQuotedBlockIndex(
        array $lines
    ): ?int {
        $lineCount = count($lines);

        for (
            $index = 0;
            $index < $lineCount;
            $index++
        ) {
            $line = ltrim(
                $lines[$index] ?? ''
            );

            if (
                ! str_starts_with(
                    $line,
                    '>'
                )
            ) {
                continue;
            }

            if (
                ! $this->hasContentBefore(
                    $lines,
                    $index
                )
            ) {
                continue;
            }

            $quotedLines = 0;
            $nonQuotedLines = 0;

            for (
                $current = $index;
                $current < $lineCount;
                $current++
            ) {
                $candidate = trim(
                    $lines[$current] ?? ''
                );

                if ($candidate === '') {
                    continue;
                }

                if (
                    str_starts_with(
                        ltrim($candidate),
                        '>'
                    )
                ) {
                    $quotedLines++;

                    continue;
                }

                $nonQuotedLines++;
            }

            if (
                $quotedLines >= 2
                && $nonQuotedLines <= 1
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function hasContentBefore(
        array $lines,
        int $index
    ): bool {
        for (
            $current = 0;
            $current < $index;
            $current++
        ) {
            if (
                trim(
                    $lines[$current] ?? ''
                ) !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function stripSignature(
        string $body
    ): array {
        if ($body === '') {
            return [
                '',
                false,
            ];
        }

        $lines = explode(
            "\n",
            $body
        );

        $startIndex = max(
            1,
            count($lines) - 12
        );

        for (
            $index = $startIndex;
            $index < count($lines);
            $index++
        ) {
            $line = trim(
                $lines[$index] ?? ''
            );

            if (
                $line === '--'
                || $line === '-- '
            ) {
                return [
                    $this->normalizeText(
                        implode(
                            "\n",
                            array_slice(
                                $lines,
                                0,
                                $index
                            )
                        )
                    ),
                    true,
                ];
            }

            if (
                preg_match(
                    '/^(?:'
                    .'sent from my (?:iphone|ipad|android)'
                    .'|sent from samsung mobile'
                    .'|get outlook for (?:ios|android)'
                    .'|отправлено с моего (?:iphone|ipad)'
                    .'|отправлено с iphone'
                    .'|отправлено из мобильной почты'
                    .'|отправлено из мобильной почты mail\.ru'
                    .')\.?$/iu',
                    $line
                ) === 1
            ) {
                return [
                    $this->normalizeText(
                        implode(
                            "\n",
                            array_slice(
                                $lines,
                                0,
                                $index
                            )
                        )
                    ),
                    true,
                ];
            }
        }

        return [
            $body,
            false,
        ];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function htmlToText(
        string $html,
        bool $stripQuotedText,
    ): array {
        $html = trim($html);

        if ($html === '') {
            return [
                '',
                false,
            ];
        }

        $previousLibxmlState =
            libxml_use_internal_errors(
                true
            );

        try {
            $document = new DOMDocument(
                '1.0',
                'UTF-8'
            );

            $wrappedHtml =
                '<?xml encoding="UTF-8">'
                .'<!DOCTYPE html>'
                .'<html>'
                .'<head>'
                .'<meta charset="UTF-8">'
                .'</head>'
                .'<body>'
                .'<div id="simpledesk-reply-parser-root">'
                .$html
                .'</div>'
                .'</body>'
                .'</html>';

            $loaded = $document->loadHTML(
                $wrappedHtml,
                LIBXML_NONET
                | LIBXML_NOERROR
                | LIBXML_NOWARNING,
            );

            if (! $loaded) {
                return [
                    $this->fallbackHtmlToText(
                        $html
                    ),
                    false,
                ];
            }

            $xpath = new DOMXPath(
                $document
            );

            $this->removeNodes(
                $xpath,
                '//script'
                .'|//style'
                .'|//head'
                .'|//template'
                .'|//noscript'
            );

            $quotedTextRemoved = false;

            if ($stripQuotedText) {
                $removedNodes = 0;

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//blockquote'
                    );

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//*[contains('
                        .'concat(" ", normalize-space(@class), " "),'
                        .'" gmail_quote "'
                        .')]'
                    );

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//*[contains('
                        .'concat(" ", normalize-space(@class), " "),'
                        .'" gmail_extra "'
                        .')]'
                    );

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//*[contains('
                        .'concat(" ", normalize-space(@class), " "),'
                        .'" yahoo_quoted "'
                        .')]'
                    );

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//*[contains('
                        .'concat(" ", normalize-space(@class), " "),'
                        .'" moz-cite-prefix "'
                        .')]'
                    );

                $removedNodes +=
                    $this->removeNodes(
                        $xpath,
                        '//*[@id="divRplyFwdMsg"]'
                        .'|//*[@id="appendonsend"]'
                    );

                $quotedTextRemoved =
                    $removedNodes > 0;
            }

            $rootNodeList = $xpath->query(
                '//*[@id="simpledesk-reply-parser-root"]'
            );

            if (
                $rootNodeList === false
                || $rootNodeList->length === 0
            ) {
                return [
                    $this->fallbackHtmlToText(
                        $html
                    ),
                    false,
                ];
            }

            $rootNode = $rootNodeList->item(
                0
            );

            if (! $rootNode instanceof DOMElement) {
                return [
                    $this->fallbackHtmlToText(
                        $html
                    ),
                    false,
                ];
            }

            $cleanHtml = $this->innerHtml(
                $rootNode
            );

            return [
                $this->fallbackHtmlToText(
                    $cleanHtml
                ),
                $quotedTextRemoved,
            ];
        } catch (Throwable) {
            return [
                $this->fallbackHtmlToText(
                    $html
                ),
                false,
            ];
        } finally {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $previousLibxmlState
            );
        }
    }

    private function removeNodes(
        DOMXPath $xpath,
        string $query
    ): int {
        $nodes = $xpath->query(
            $query
        );

        if ($nodes === false) {
            return 0;
        }

        $items = [];

        foreach ($nodes as $node) {
            $items[] = $node;
        }

        $removed = 0;

        foreach (
            array_reverse($items) as $node
        ) {
            if (
                ! $node instanceof DOMNode
                || $node->parentNode === null
            ) {
                continue;
            }

            $node->parentNode->removeChild(
                $node
            );

            $removed++;
        }

        return $removed;
    }

    private function innerHtml(
        DOMNode $node
    ): string {
        $document =
            $node->ownerDocument;

        if (! $document instanceof DOMDocument) {
            return '';
        }

        $html = '';

        foreach ($node->childNodes as $child) {
            $childHtml =
                $document->saveHTML(
                    $child
                );

            if (is_string($childHtml)) {
                $html .= $childHtml;
            }
        }

        return $html;
    }

    private function fallbackHtmlToText(
        string $html
    ): string {
        $html = preg_replace(
            '/<br\s*\/?>/iu',
            "\n",
            $html
        );

        $html = preg_replace(
            '/<\/(?:'
            .'p|div|section|article|li|tr|td'
            .'|h1|h2|h3|h4|h5|h6'
            .')>/iu',
            "\n",
            (string) $html
        );

        $text = strip_tags(
            (string) $html
        );

        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $text = str_replace(
            "\u{00A0}",
            ' ',
            $text
        );

        return $this->normalizeText(
            $text
        );
    }

    private function normalizeText(
        string $text
    ): string {
        $text = str_replace(
            [
                "\r\n",
                "\r",
                "\u{200B}",
                "\u{FEFF}",
            ],
            [
                "\n",
                "\n",
                '',
                '',
            ],
            $text
        );

        $text = preg_replace(
            '/[ \t]+\n/u',
            "\n",
            $text
        );

        $text = preg_replace(
            '/\n[ \t]+/u',
            "\n",
            (string) $text
        );

        $text = preg_replace(
            '/\n{3,}/u',
            "\n\n",
            (string) $text
        );

        return trim(
            (string) $text
        );
    }

    private function limitBody(
        string $body
    ): string {
        $limit = max(
            1000,
            (int) config(
                'simpledesk-mail-reply-parsing.max_body_characters',
                200000
            )
        );

        if (
            mb_strlen($body)
            <= $limit
        ) {
            return $body;
        }

        return rtrim(
                mb_substr(
                    $body,
                    0,
                    $limit
                )
            )."\n\n[Содержимое сокращено системой]";
    }
}
