import { Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'
import type { ReactNode } from 'react'

type BreadcrumbItem = {
    label: string
    href?: string
    icon?: ReactNode
}

type Props = {
    readonly items: readonly BreadcrumbItem[]
    readonly className?: string
}

export default function Breadcrumbs({ items, className = '' }: Props) {
    return (
        <div className={`flex flex-wrap items-center gap-2 text-sm text-gray-500 ${className}`}>
            {items.map((item, index) => {
                const isLast = index === items.length - 1

                return (
                    <div key={`${item.label}-${index}`} className="flex items-center gap-2">
                        {item.href && !isLast ? (
                            <Link
                                href={item.href}
                                className="inline-flex items-center gap-2 transition hover:text-gray-900"
                            >
                                {item.icon}
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                className={
                                    isLast
                                        ? 'font-medium text-gray-900'
                                        : 'inline-flex items-center gap-2'
                                }
                            >
                                {item.icon}
                                {item.label}
                            </span>
                        )}

                        {!isLast && <ChevronRight className="h-4 w-4" />}
                    </div>
                )
            })}
        </div>
    )
}
