import {
    useEffect,
    useRef,
    useState,
} from 'react'
import {
    ArrowDown,
    ArrowUp,
    Check,
    ChevronDown,
    Plus,
    Trash2,
    X,
} from 'lucide-react'
import SkillSelect from './SkillSelect'
import type {
    RuleField,
    SkillRule,
} from './skillTypes'

type Props = {
    rules: SkillRule[]
    schema: RuleField[]
    errors: Record<string, string>
    onChange: (rules: SkillRule[]) => void
}

type RuleValueItem = string | number

type RuleOption = {
    value: string | number
    label: string
}

const noValueOperators = new Set([
    'is_empty',
    'is_not_empty',
    'is_true',
    'is_false',
])

const multiValueOperators = new Set([
    'in',
    'not_in',
])

export default function SkillRuleBuilder({
                                             rules,
                                             schema,
                                             errors,
                                             onChange,
                                         }: Props) {
    const changeField = (
        index: number,
        key: string,
    ) => {
        const field = schema.find(
            (item) => item.key === key,
        )

        if (!field) {
            return
        }

        const operator =
            field.operators[0] ?? ''

        const next = [...rules]

        next[index] = {
            field_key: key,
            operator,
            value: defaultValueForOperator(
                operator,
            ),
        }

        onChange(next)
    }

    const changeOperator = (
        index: number,
        operator: string,
    ) => {
        const next = [...rules]

        next[index] = {
            ...next[index],
            operator,
            value:
                defaultValueForOperator(
                    operator,
                ),
        }

        onChange(next)
    }

    const updateValue = (
        index: number,
        value: SkillRule['value'],
    ) => {
        const next = [...rules]

        next[index] = {
            ...next[index],
            value,
        }

        onChange(next)
    }

    const add = () => {
        const field = schema[0]

        if (!field) {
            return
        }

        const operator =
            field.operators[0] ?? ''

        onChange([
            ...rules,
            {
                field_key: field.key,
                operator,
                value:
                    defaultValueForOperator(
                        operator,
                    ),
            },
        ])
    }

    const remove = (index: number) => {
        if (rules.length <= 1) {
            return
        }

        onChange(
            rules.filter(
                (_, ruleIndex) =>
                    ruleIndex !== index,
            ),
        )
    }

    const move = (
        index: number,
        direction: -1 | 1,
    ) => {
        const target = index + direction

        if (
            target < 0 ||
            target >= rules.length
        ) {
            return
        }

        const next = [...rules]

        ;[
            next[index],
            next[target],
        ] = [
            next[target],
            next[index],
        ]

        onChange(next)
    }

    if (schema.length === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center">
                <div className="text-sm font-semibold text-gray-700">
                    No rule fields available
                </div>

                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                    There are currently no
                    ticket fields available for
                    skill conditions.
                </p>
            </div>
        )
    }

    return (
        <div className="space-y-4">
            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h3 className="text-sm font-semibold text-gray-900">
                        Rule set
                    </h3>

                    <p className="mt-1 text-xs leading-5 text-gray-500">
                        Conditions are evaluated
                        in the order shown below.
                    </p>
                </div>

                <span className="inline-flex w-fit rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                    {rules.length}{' '}
                    {rules.length === 1
                        ? 'condition'
                        : 'conditions'}
                </span>
            </div>

            <div className="space-y-3">
                {rules.map(
                    (rule, index) => {
                        const field =
                            schema.find(
                                (item) =>
                                    item.key ===
                                    rule.field_key,
                            ) ?? schema[0]

                        const options =
                            field.options ?? []

                        const noValue =
                            noValueOperators.has(
                                rule.operator,
                            )

                        const multiValue =
                            multiValueOperators.has(
                                rule.operator,
                            )

                        const isBetween =
                            rule.operator ===
                            'between'

                        return (
                            <RuleCard
                                key={index}
                                index={index}
                                total={
                                    rules.length
                                }
                                onMoveUp={() =>
                                    move(
                                        index,
                                        -1,
                                    )
                                }
                                onMoveDown={() =>
                                    move(
                                        index,
                                        1,
                                    )
                                }
                                onRemove={() =>
                                    remove(index)
                                }
                            >
                                <div className="grid gap-4 xl:grid-cols-[minmax(180px,0.95fr)_minmax(190px,0.95fr)_minmax(240px,1.35fr)]">
                                    <RuleControl
                                        label="Field"
                                        error={
                                            errors[
                                                `rules.${index}.field_key`
                                                ]
                                        }
                                    >
                                        <SkillSelect
                                            value={
                                                rule.field_key
                                            }
                                            options={schema.map(
                                                (
                                                    item,
                                                ) => ({
                                                    value:
                                                    item.key,
                                                    label:
                                                    item.label,
                                                }),
                                            )}
                                            onChange={(
                                                value,
                                            ) =>
                                                changeField(
                                                    index,
                                                    String(
                                                        value,
                                                    ),
                                                )
                                            }
                                        />
                                    </RuleControl>

                                    <RuleControl
                                        label="Operator"
                                        error={
                                            errors[
                                                `rules.${index}.operator`
                                                ]
                                        }
                                    >
                                        <SkillSelect
                                            value={
                                                rule.operator
                                            }
                                            options={field.operators.map(
                                                (
                                                    operator,
                                                ) => ({
                                                    value:
                                                    operator,
                                                    label:
                                                        formatLabel(
                                                            operator,
                                                        ),
                                                }),
                                            )}
                                            onChange={(
                                                value,
                                            ) =>
                                                changeOperator(
                                                    index,
                                                    String(
                                                        value,
                                                    ),
                                                )
                                            }
                                        />
                                    </RuleControl>

                                    <RuleControl
                                        label="Value"
                                        error={
                                            errors[
                                                `rules.${index}.value`
                                                ]
                                        }
                                    >
                                        {noValue ? (
                                            <NoValueControl />
                                        ) : multiValue ? (
                                            <MultiValueSelect
                                                value={toValueArray(
                                                    rule.value,
                                                )}
                                                options={options}
                                                placeholder={`Select ${field.label.toLowerCase()}...`}
                                                onChange={(value) =>
                                                    updateValue(
                                                        index,
                                                        value,
                                                    )
                                                }
                                            />
                                        ) : isBetween ? (
                                            <BetweenValue
                                                value={
                                                    rule.value
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    updateValue(
                                                        index,
                                                        value,
                                                    )
                                                }
                                            />
                                        ) : options.length >
                                        0 ? (
                                            <SkillSelect
                                                value={
                                                    Array.isArray(
                                                        rule.value,
                                                    )
                                                        ? ''
                                                        : rule.value ??
                                                        ''
                                                }
                                                options={
                                                    options
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    updateValue(
                                                        index,
                                                        value,
                                                    )
                                                }
                                            />
                                        ) : (
                                            <SingleValueInput
                                                value={
                                                    rule.value
                                                }
                                                onChange={(
                                                    value,
                                                ) =>
                                                    updateValue(
                                                        index,
                                                        value,
                                                    )
                                                }
                                            />
                                        )}
                                    </RuleControl>
                                </div>
                            </RuleCard>
                        )
                    },
                )}
            </div>

            <button
                type="button"
                onClick={add}
                className="group flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-violet-300 bg-violet-50/40 px-4 py-3.5 text-sm font-semibold text-violet-700 transition hover:border-violet-400 hover:bg-violet-50"
            >
                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100 transition group-hover:bg-violet-200">
                    <Plus className="h-4 w-4" />
                </span>

                Add condition
            </button>

            <Error text={errors.rules} />
        </div>
    )
}

function RuleCard({
                      index,
                      total,
                      onMoveUp,
                      onMoveDown,
                      onRemove,
                      children,
                  }: {
    index: number
    total: number
    onMoveUp: () => void
    onMoveDown: () => void
    onRemove: () => void
    children: React.ReactNode
}) {
    const first = index === 0
    const last = index === total - 1
    const only = total === 1

    return (
        <div className="relative z-0 overflow-visible rounded-2xl border border-gray-200 bg-white shadow-sm focus-within:z-40">
            <div className="flex items-center justify-between gap-3 rounded-t-2xl border-b border-gray-100 bg-gray-50/70 px-4 py-3">
                <div className="flex min-w-0 items-center gap-3">
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-xs font-bold text-violet-700">
                        {index + 1}
                    </span>

                    <div className="min-w-0">
                        <div className="text-sm font-semibold text-gray-800">
                            Condition{' '}
                            {index + 1}
                        </div>
                    </div>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                    <RuleActionButton
                        label="Move condition up"
                        disabled={first}
                        onClick={onMoveUp}
                    >
                        <ArrowUp className="h-4 w-4" />
                    </RuleActionButton>

                    <RuleActionButton
                        label="Move condition down"
                        disabled={last}
                        onClick={onMoveDown}
                    >
                        <ArrowDown className="h-4 w-4" />
                    </RuleActionButton>

                    <RuleActionButton
                        label="Delete condition"
                        disabled={only}
                        danger
                        onClick={onRemove}
                    >
                        <Trash2 className="h-4 w-4" />
                    </RuleActionButton>
                </div>
            </div>

            <div className="p-4">
                {children}
            </div>
        </div>
    )
}

function RuleControl({
                         label,
                         error,
                         children,
                     }: {
    label: string
    error?: string
    children: React.ReactNode
}) {
    return (
        <div className="min-w-0">
            <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                {label}
            </div>

            {children}

            <Error text={error} />
        </div>
    )
}

function RuleActionButton({
                              label,
                              disabled,
                              danger = false,
                              onClick,
                              children,
                          }: {
    label: string
    disabled: boolean
    danger?: boolean
    onClick: () => void
    children: React.ReactNode
}) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            onClick={onClick}
            className={`inline-flex h-8 w-8 items-center justify-center rounded-lg border bg-white transition ${
                disabled
                    ? 'cursor-not-allowed border-gray-200 text-gray-300'
                    : danger
                        ? 'border-gray-200 text-gray-400 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600'
                        : 'border-gray-200 text-gray-400 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700'
            }`}
        >
            {children}
        </button>
    )
}

function NoValueControl() {
    return (
        <div className="flex h-11 items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 text-sm text-gray-400">
            <Check className="h-4 w-4 shrink-0 text-gray-300" />

            No value required
        </div>
    )
}

function SingleValueInput({
                              value,
                              onChange,
                          }: {
    value: SkillRule['value']
    onChange: (
        value: SkillRule['value'],
    ) => void
}) {
    return (
        <input
            type="text"
            value={
                Array.isArray(value)
                    ? ''
                    : value == null
                        ? ''
                        : String(value)
            }
            onChange={(event) =>
                onChange(event.target.value)
            }
            placeholder="Enter value..."
            className="h-11 w-full rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
        />
    )
}

function BetweenValue({
                          value,
                          onChange,
                      }: {
    value: SkillRule['value']
    onChange: (
        value: SkillRule['value'],
    ) => void
}) {
    const values = Array.isArray(value)
        ? value
        : []

    return (
        <div className="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <input
                type="text"
                value={String(
                    values[0] ?? '',
                )}
                onChange={(event) =>
                    onChange([
                        event.target.value,
                        values[1] ?? '',
                    ])
                }
                placeholder="From"
                className="h-11 min-w-0 rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
            />

            <span className="text-xs font-medium text-gray-400">
                to
            </span>

            <input
                type="text"
                value={String(
                    values[1] ?? '',
                )}
                onChange={(event) =>
                    onChange([
                        values[0] ?? '',
                        event.target.value,
                    ])
                }
                placeholder="To"
                className="h-11 min-w-0 rounded-xl border border-gray-200 bg-white px-3.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 focus:border-violet-400 focus:ring-4 focus:ring-violet-100"
            />
        </div>
    )
}

function MultiValueSelect({
                              value,
                              options,
                              placeholder,
                              onChange,
                          }: {
    value: RuleValueItem[]
    options: RuleOption[]
    placeholder: string
    onChange: (
        value: RuleValueItem[],
    ) => void
}) {
    const [open, setOpen] =
        useState(false)

    const rootRef =
        useRef<HTMLDivElement>(null)

    const selectedValues =
        value.map(String)

    const selectedOptions =
        options.filter((option) =>
            selectedValues.includes(
                String(option.value),
            ),
        )

    useEffect(() => {
        const onOutsideClick = (
            event: MouseEvent,
        ) => {
            if (
                rootRef.current &&
                !rootRef.current.contains(
                    event.target as Node,
                )
            ) {
                setOpen(false)
            }
        }

        const onEscape = (
            event: KeyboardEvent,
        ) => {
            if (event.key === 'Escape') {
                setOpen(false)
            }
        }

        document.addEventListener(
            'mousedown',
            onOutsideClick,
        )

        document.addEventListener(
            'keydown',
            onEscape,
        )

        return () => {
            document.removeEventListener(
                'mousedown',
                onOutsideClick,
            )

            document.removeEventListener(
                'keydown',
                onEscape,
            )
        }
    }, [])

    const toggle = (
        option: RuleOption,
    ) => {
        const selected =
            selectedValues.includes(
                String(option.value),
            )

        if (selected) {
            onChange(
                value.filter(
                    (current) =>
                        String(current) !==
                        String(
                            option.value,
                        ),
                ),
            )

            return
        }

        onChange([
            ...value,
            option.value,
        ])
    }

    const remove = (
        option: RuleOption,
    ) => {
        onChange(
            value.filter(
                (current) =>
                    String(current) !==
                    String(option.value),
            ),
        )
    }

    return (
        <div
            ref={rootRef}
            className="relative"
        >
            <button
                type="button"
                role="combobox"
                aria-expanded={open}
                onClick={() =>
                    setOpen(
                        (current) =>
                            !current,
                    )
                }
                className={`flex min-h-11 w-full items-center gap-2 rounded-xl border bg-white px-3 py-2 text-left outline-none transition ${
                    open
                        ? 'border-violet-400 ring-4 ring-violet-100'
                        : 'border-gray-200 hover:border-gray-300'
                }`}
            >
                <div className="flex min-w-0 flex-1 flex-wrap gap-1.5">
                    {selectedOptions.length >
                    0 ? (
                        selectedOptions
                            .slice(0, 3)
                            .map((option) => (
                                <span
                                    key={String(
                                        option.value,
                                    )}
                                    className="inline-flex max-w-[150px] items-center gap-1 rounded-lg bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-700"
                                >
                                    <span className="truncate">
                                        {
                                            option.label
                                        }
                                    </span>

                                    <span
                                        role="button"
                                        tabIndex={0}
                                        onClick={(
                                            event,
                                        ) => {
                                            event.stopPropagation()

                                            remove(
                                                option,
                                            )
                                        }}
                                        onKeyDown={(
                                            event,
                                        ) => {
                                            if (
                                                event.key ===
                                                'Enter' ||
                                                event.key ===
                                                ' '
                                            ) {
                                                event.preventDefault()
                                                event.stopPropagation()

                                                remove(
                                                    option,
                                                )
                                            }
                                        }}
                                        className="rounded text-violet-400 transition hover:text-violet-800"
                                    >
                                        <X className="h-3 w-3" />
                                    </span>
                                </span>
                            ))
                    ) : (
                        <span className="py-1 text-sm text-gray-400">
                            {placeholder}
                        </span>
                    )}

                    {selectedOptions.length >
                    3 ? (
                        <span className="inline-flex items-center rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500">
                            +
                            {selectedOptions.length -
                                3}
                        </span>
                    ) : null}
                </div>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                        open
                            ? 'rotate-180'
                            : ''
                    }`}
                />
            </button>

            {open ? (
                <div className="absolute left-0 right-0 z-50 mt-2 max-h-64 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-2 shadow-xl">
                    {options.length > 0 ? (
                        options.map(
                            (option) => {
                                const selected =
                                    selectedValues.includes(
                                        String(
                                            option.value,
                                        ),
                                    )

                                return (
                                    <button
                                        key={String(
                                            option.value,
                                        )}
                                        type="button"
                                        onClick={() =>
                                            toggle(
                                                option,
                                            )
                                        }
                                        className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition ${
                                            selected
                                                ? 'bg-violet-50'
                                                : 'hover:bg-gray-50'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-md border ${
                                                selected
                                                    ? 'border-violet-600 bg-violet-600 text-white'
                                                    : 'border-gray-300 bg-white'
                                            }`}
                                        >
                                            {selected ? (
                                                <Check className="h-3 w-3" />
                                            ) : null}
                                        </span>

                                        <span
                                            className={`min-w-0 flex-1 truncate text-sm ${
                                                selected
                                                    ? 'font-semibold text-violet-700'
                                                    : 'font-medium text-gray-700'
                                            }`}
                                        >
                                            {
                                                option.label
                                            }
                                        </span>
                                    </button>
                                )
                            },
                        )
                    ) : (
                        <div className="px-3 py-6 text-center text-sm text-gray-400">
                            No values available
                        </div>
                    )}
                </div>
            ) : null}
        </div>
    )
}

function Error({
                   text,
               }: {
    text?: string
}) {
    if (!text) {
        return null
    }

    return (
        <p className="mt-1.5 text-xs font-medium text-rose-600">
            {text}
        </p>
    )
}

function toValueArray(
    value: SkillRule['value'],
): RuleValueItem[] {
    if (!Array.isArray(value)) {
        return []
    }

    return value.filter(
        (
            item,
        ): item is RuleValueItem =>
            typeof item === 'string' ||
            typeof item === 'number',
    )
}

function defaultValueForOperator(
    operator: string,
): SkillRule['value'] {
    if (
        multiValueOperators.has(operator) ||
        operator === 'between'
    ) {
        return []
    }

    return null
}

function formatLabel(
    value: string,
): string {
    return value
        .replace(/[_-]+/g, ' ')
        .trim()
        .split(/\s+/)
        .map(
            (part) =>
                part.charAt(0).toUpperCase() +
                part.slice(1),
        )
        .join(' ')
}
