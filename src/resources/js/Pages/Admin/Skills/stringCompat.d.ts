export {}

declare global {
    interface String {
        replaceAll(searchValue: string, replaceValue: string): string
    }
}
