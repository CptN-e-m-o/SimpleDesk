export type SkillOption = { value: string | number; label: string }
export type RuleField = { key: string; label: string; type: string; operators: string[]; multiple: boolean; options?: SkillOption[] }
export type SkillRule = { field_key: string; operator: string; value: string | number | Array<string | number> | null }
export type Skill = { id: number; name: string; slug: string; description?: string | null; match_type: 'any' | 'all'; is_active: boolean; sort_order: number; version: number; rules: SkillRule[]; rules_count: number; updated_at: string; deleted_at?: string | null }
export type PaginationLink = { url?: string | null; label: string; active: boolean }
