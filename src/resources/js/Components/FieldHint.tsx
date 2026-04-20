import { HelpCircle } from 'lucide-react'
import { useId, useState } from 'react'

type Props = {
    readonly text: string
    readonly side?: 'top' | 'bottom'
}

export default function FieldHint({
                                      text,
                                      side = 'top',
                                  }: Props) {
    const [open, setOpen] = useState(false)
    const tooltipId = useId()

    const tooltipPositionClass =
        side === 'bottom'
            ? 'top-full mt-3'
            : 'bottom-full mb-3'

    const arrowPositionClass =
        side === 'bottom'
            ? '-top-1.5'
            : '-bottom-1.5'

    return (
        <div className="relative inline-flex shrink-0">
            <button
                type="button"
                aria-label="Show field help"
                aria-describedby={open ? tooltipId : undefined}
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
                onFocus={() => setOpen(true)}
                onBlur={() => setOpen(false)}
                onClick={() => setOpen((prev) => !prev)}
                className="inline-flex h-5 w-5 items-center justify-center rounded-full text-sky-500 transition hover:bg-sky-50 hover:text-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-100"
            >
                <HelpCircle className="h-4 w-4" />
            </button>

            {open && (
                <div
                    id={tooltipId}
                    role="tooltip"
                    className={`absolute left-0 z-30 w-80 rounded-2xl bg-slate-950 px-4 py-3 text-sm leading-6 text-white shadow-2xl ${tooltipPositionClass}`}
                >
                    <div
                        className={`absolute left-4 h-3 w-3 rotate-45 bg-slate-950 ${arrowPositionClass}`}
                    />

                    {text}
                </div>
            )}
        </div>
    )
}
