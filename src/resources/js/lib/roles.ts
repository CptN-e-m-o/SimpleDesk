export function hasRole(user: { roles?: string[] } | null, role: string) {
    return user?.roles?.includes(role)
}

export function hasAnyRole(
    user: { roles?: string[] } | null,
    roles: string[]
) {
    return roles.some((role) => user?.roles?.includes(role))
}

export function isAdminOrAgent(user: { roles?: string[] } | null) {
    return hasAnyRole(user, ['admin', 'agent'])
}

export function isAdmin(user: { roles?: string[] } | null) {
    return hasRole(user, 'admin')
}

export function isAgent(user: { roles?: string[] } | null) {
    return hasRole(user, 'agent')
}
