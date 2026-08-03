import AdminLayout from '@/Layouts/AdminLayout'
import { Head } from '@inertiajs/react'
import { RuleForm, SelectOption } from './shared'

export default function Create({ patternTypes, contentTypes }: { readonly patternTypes: SelectOption[]; readonly contentTypes: SelectOption[] }) {
    return <AdminLayout title="Create Parsing Rule"><Head title="Create Parsing Rule" /><RuleForm patternTypes={patternTypes} contentTypes={contentTypes} /></AdminLayout>
}
