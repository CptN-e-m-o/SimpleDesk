import InputError from '@/Components/InputError'
import { Check, ChevronDown, Search, X } from 'lucide-react'
import {
    getCountries,
    getCountryCallingCode,
} from 'react-phone-number-input/input'
import en from 'react-phone-number-input/locale/en.json'
import { useMemo, useRef, useState, useEffect } from 'react'
import ReactCountryFlag from 'react-country-flag'

type Props = {
    readonly id: string
    readonly label: string
    readonly iso2: string
    readonly countryCode: string
    readonly number: string
    readonly ext?: string
    readonly error?: string
    readonly extError?: string
    readonly withExt?: boolean
    readonly onChangeIso2: (value: string) => void
    readonly onChangeCountryCode: (value: string) => void
    readonly onChangeNumber: (value: string) => void
    readonly onChangeExt?: (value: string) => void
}

type CountryOption = {
    readonly iso2: string
    readonly name: string
    readonly code: string
}

function closeDropdownOnOutside(
    element: HTMLElement | null,
    target: Node,
    close: () => void,
) {
    if (!element?.contains(target)) {
        close()
    }
}

export default function PhoneCountryInput({
                                              id,
                                              label,
                                              iso2,
                                              countryCode,
                                              number,
                                              ext = '',
                                              error,
                                              extError,
                                              withExt = false,
                                              onChangeIso2,
                                              onChangeCountryCode,
                                              onChangeNumber,
                                              onChangeExt,
                                          }: Props) {
    const [isOpen, setIsOpen] = useState(false)
    const [search, setSearch] = useState('')
    const containerRef = useRef<HTMLDivElement | null>(null)

    const countries = useMemo<CountryOption[]>(() => {
        return getCountries()
            .map((country) => {
                const code = `+${getCountryCallingCode(country)}`
                const name = en[country] ?? country

                return {
                    iso2: country,
                    name,
                    code,
                }
            })
            .sort((a, b) => a.name.localeCompare(b.name))
    }, [])

    const selectedCountry = useMemo(() => {
        return (
            countries.find((country) => country.iso2 === iso2) ??
            countries.find((country) => country.code === countryCode) ??
            countries[0]
        )
    }, [countries, countryCode, iso2])

    const filteredCountries = useMemo(() => {
        const query = search.trim().toLowerCase()

        if (!query) return countries

        return countries.filter((country) => {
            return (
                country.name.toLowerCase().includes(query) ||
                country.iso2.toLowerCase().includes(query) ||
                country.code.includes(query)
            )
        })
    }, [countries, search])

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            closeDropdownOnOutside(
                containerRef.current,
                event.target as Node,
                () => setIsOpen(false),
            )
        }

        document.addEventListener('mousedown', handleClickOutside)

        return () => {
            document.removeEventListener('mousedown', handleClickOutside)
        }
    }, [])

    function selectCountry(country: CountryOption) {
        onChangeIso2(country.iso2)
        onChangeCountryCode(country.code)
        setIsOpen(false)
        setSearch('')
    }

    return (
        <div>
            <label
                htmlFor={id}
                className="mb-2 block text-sm font-medium text-gray-700"
            >
                {label}
            </label>

            <div
                ref={containerRef}
                className="relative grid gap-3 sm:grid-cols-[minmax(180px,220px)_minmax(0,1fr)]"
            >
                <button
                    type="button"
                    onClick={() => setIsOpen((prev) => !prev)}
                    className={`flex h-12 items-center justify-between gap-3 rounded-2xl border bg-white px-4 text-left text-sm transition ${
                        isOpen
                            ? 'border-sky-300 ring-4 ring-sky-100'
                            : 'border-gray-200 hover:border-gray-300'
                    }`}
                >
                    <span className="flex min-w-0 items-center gap-2">
                        {selectedCountry && (
                            <ReactCountryFlag
                                countryCode={selectedCountry.iso2}
                                svg
                                className="shrink-0 rounded-sm"
                                style={{
                                    width: '1.35em',
                                    height: '1.35em',
                                }}
                            />
                        )}

                        <span className="truncate font-medium text-gray-900">
                            {selectedCountry?.code}
                        </span>
                    </span>

                    <ChevronDown
                        className={`h-4 w-4 shrink-0 text-gray-400 transition ${
                            isOpen ? 'rotate-180' : ''
                        }`}
                    />
                </button>

                <input
                    id={id}
                    type="tel"
                    value={number}
                    onChange={(event) => onChangeNumber(event.target.value)}
                    placeholder="Phone number"
                    className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                />

                {isOpen && (
                    <div className="absolute left-0 top-14 z-30 w-full overflow-hidden rounded-[24px] border border-gray-200 bg-white shadow-xl shadow-gray-900/10 sm:w-[420px]">
                        <div className="border-b border-gray-100 p-3">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Search country or code..."
                                    className="h-10 w-full rounded-2xl border border-gray-200 bg-white pl-10 pr-9 text-sm outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                                />

                                {search && (
                                    <button
                                        type="button"
                                        onClick={() => setSearch('')}
                                        className="absolute right-3 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                    >
                                        <X className="h-3.5 w-3.5" />
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="max-h-72 overflow-y-auto p-2">
                            {filteredCountries.map((country) => {
                                const selected = country.iso2 === iso2

                                return (
                                    <button
                                        key={country.iso2}
                                        type="button"
                                        onClick={() => selectCountry(country)}
                                        className={`flex w-full items-center justify-between gap-3 rounded-2xl px-3 py-3 text-left text-sm transition ${
                                            selected
                                                ? 'bg-sky-50 text-sky-700'
                                                : 'text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <span className="flex min-w-0 items-center gap-3">
                                            <ReactCountryFlag
                                                countryCode={country.iso2}
                                                svg
                                                className="shrink-0 rounded-sm"
                                                style={{
                                                    width: '1.35em',
                                                    height: '1.35em',
                                                }}
                                            />
                                            <span className="min-w-0">
                                                <span className="block truncate font-medium">
                                                    {country.name}
                                                </span>
                                                <span className="mt-0.5 block text-xs text-gray-500">
                                                    {country.code} · {country.iso2}
                                                </span>
                                            </span>
                                        </span>

                                        {selected && (
                                            <Check className="h-4 w-4 shrink-0" />
                                        )}
                                    </button>
                                )
                            })}
                        </div>
                    </div>
                )}
            </div>

            {withExt && (
                <div className="mt-3">
                    <input
                        type="text"
                        value={ext}
                        onChange={(event) => onChangeExt?.(event.target.value)}
                        placeholder="Extension"
                        className="h-12 w-full rounded-2xl border border-gray-200 bg-white px-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-sky-300 focus:ring-4 focus:ring-sky-100"
                    />

                    <InputError message={extError} className="mt-2" />
                </div>
            )}

            <InputError message={error} className="mt-2" />
        </div>
    )
}
