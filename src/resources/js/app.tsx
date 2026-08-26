import '../css/app.css'
import './bootstrap'

import { createRoot } from 'react-dom/client'
import { createInertiaApp, router } from '@inertiajs/react'
import type { ComponentType } from 'react'
import type { Config as ZiggyConfig } from 'ziggy-js'

import { configureRealtime } from '@/lib/realtime'
import type { SharedData } from '@/types'

const pages = import.meta.glob('./Pages/**/*.tsx', {
    eager: true,
}) as Record<string, { default: ComponentType }>

declare global {
    var Ziggy: ZiggyConfig
}

createInertiaApp<SharedData>({
    defaults: {
        future: {
            useDataInertiaHeadAttribute: true,
        },
    },

    resolve: (name) => {
        const page = pages[`./Pages/${name}.tsx`]

        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }

        return page.default
    },

    setup({ el, App, props }) {
        globalThis.Ziggy = props.initialPage.props.ziggy as ZiggyConfig

        configureRealtime(
            props.initialPage.props.broadcastingClient,
        )

        router.on('navigate', (event) => {
            const pageProps = event.detail.page.props as Partial<SharedData>

            configureRealtime(
                pageProps.broadcastingClient,
            )
        })

        createRoot(el).render(
            <App {...props} />,
        )
    },
})
