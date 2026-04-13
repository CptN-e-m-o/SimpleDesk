import '../css/app.css'
import './bootstrap'

import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import type { ComponentType } from 'react'

const pages = import.meta.glob<{ default: ComponentType<any> }>('./Pages/**/*.tsx')

createInertiaApp({
    resolve: async (name) => {
        const page = await pages[`./Pages/${name}.tsx`]?.()

        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }

        return page.default
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
