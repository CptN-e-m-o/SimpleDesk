import '../css/app.css'
import './bootstrap'

import { createRoot } from 'react-dom/client'
import { createInertiaApp } from '@inertiajs/react'
import type { ComponentType } from 'react'
import type { Config as ZiggyConfig } from 'ziggy-js'

const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true }) as Record<
    string,
    { default: ComponentType }
>

declare global {
    interface Window {
        Ziggy: ZiggyConfig
    }
}

createInertiaApp({
    resolve: (name) => {
        const page = pages[`./Pages/${name}.tsx`]

        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }

        return page.default
    },

    setup({ el, App, props }) {
        window.Ziggy = props.initialPage.props.ziggy as ZiggyConfig

        createRoot(el).render(<App {...props} />)
    },
})
