import AdminLayout from '@/Layouts/AdminLayout'
import { Head } from '@inertiajs/react'
import { ReplyParsingRule, RuleForm, SelectOption } from './shared'

export default function Edit({ rule, patternTypes, contentTypes }: { readonly rule: ReplyParsingRule; readonly patternTypes: SelectOption[]; readonly contentTypes: SelectOption[] }) {
    return <AdminLayout title="Edit Parsing Rule"><Head title="Edit Parsing Rule" /><RuleForm rule={rule} patternTypes={patternTypes} contentTypes={contentTypes} /></AdminLayout>
}
