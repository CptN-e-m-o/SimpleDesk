import {
    useEffect,
    useRef,
    useState,
} from 'react'
import {
    Check,
    ChevronDown,
} from 'lucide-react'
import type { SkillOption } from './skillTypes'

type Props = {
    value: string | number
    options: SkillOption[]
    onChange: (
        value: string | number,
    ) => void
    placeholder?: string
    disabled?: boolean
}

export default function SkillSelect({
                                        value,
                                        options,
                                        onChange,
                                        placeholder = 'Choose an option',
                                        disabled = false,
                                    }: Props) {
    const [open, setOpen] =
        useState(false)

    const rootRef =
        useRef<HTMLDivElement>(null)

    const selected = options.find(
        (option) =>
            String(option.value) ===
            String(value),
    )

    useEffect(() => {
        const closeOnOutsideClick = (
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

        const closeOnEscape = (
            event: KeyboardEvent,
        ) => {
            if (event.key === 'Escape') {
                setOpen(false)
            }
        }

        document.addEventListener(
            'mousedown',
            closeOnOutsideClick,
        )

        document.addEventListener(
            'keydown',
            closeOnEscape,
        )

        return () => {
            document.removeEventListener(
                'mousedown',
                closeOnOutsideClick,
            )

            document.removeEventListener(
                'keydown',
                closeOnEscape,
            )
        }
    }, [])

    useEffect(() => {
        if (disabled) {
            setOpen(false)
        }
    }, [disabled])

    const selectOption = (
        option: SkillOption,
    ) => {
        onChange(option.value)
        setOpen(false)
    }

    return (
        <div
            ref={rootRef}
            className="relative z-0 focus-within:z-50"
        >
            <button
                type="button"
                role="combobox"
                aria-expanded={open}
                disabled={disabled}
                onClick={() =>
                    setOpen(
                        (current) =>
                            !current,
                    )
                }
                className={`flex h-11 w-full items-center gap-3 rounded-xl border bg-white px-3.5 text-left text-sm outline-none transition ${
                    disabled
                        ? 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 opacity-70'
                        : open
                            ? 'border-violet-400 ring-4 ring-violet-100'
                            : 'border-gray-200 hover:border-gray-300'
                }`}
            >
                <span
                    className={`min-w-0 flex-1 truncate ${
                        selected
                            ? 'font-medium text-gray-800'
                            : 'text-gray-400'
                    }`}
                >
                    {selected?.label ??
                        placeholder}
                </span>

                <ChevronDown
                    className={`h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 ${
                        open
                            ? 'rotate-180'
                            : ''
                    }`}
                />
            </button>

            {open && !disabled ? (
                <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-64 min-w-full overflow-y-auto rounded-2xl border border-gray-200 bg-white p-1.5 shadow-xl">
                    {options.length > 0 ? (
                        options.map(
                            (option) => {
                                const isSelected =
                                    String(
                                        option.value,
                                    ) ===
                                    String(value)

                                return (
                                    <button
                                        type="button"
                                        key={String(
                                            option.value,
                                        )}
                                        onClick={() =>
                                            selectOption(
                                                option,
                                            )
                                        }
                                        className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left transition ${
                                            isSelected
                                                ? 'bg-violet-50'
                                                : 'hover:bg-gray-50'
                                        }`}
                                    >
                                        <span
                                            className={`min-w-0 flex-1 truncate text-sm ${
                                                isSelected
                                                    ? 'font-semibold text-violet-700'
                                                    : 'font-medium text-gray-700'
                                            }`}
                                        >
                                            {
                                                option.label
                                            }
                                        </span>

                                        {isSelected ? (
                                            <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
                                                <Check className="h-3 w-3" />
                                            </span>
                                        ) : null}
                                    </button>
                                )
                            },
                        )
                    ) : (
                        <div className="px-3 py-6 text-center">
                            <p className="text-sm font-medium text-gray-500">
                                No options available
                            </p>
                        </div>
                    )}
                </div>
            ) : null}
        </div>
    )
}
