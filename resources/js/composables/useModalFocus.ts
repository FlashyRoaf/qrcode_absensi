import { nextTick, onUnmounted, watch, type Ref } from 'vue'

// Focus trap + Escape close for a modal, with focus returned to
// the element that opened it. Wire it once per modal:
//   const modalRef = ref<HTMLElement | null>(null)
//   useModalFocus(modalRef, showModal, closeModal)
// The modal element itself (the overlay) is the trap container, so
// the trap covers the dialog and its close/submit buttons.
const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

export function useModalFocus(
    modalRef: Ref<HTMLElement | null>,
    isOpen: Ref<boolean>,
    onClose: () => void,
) {
    let previouslyFocused: Element | null = null

    const onKeyDown = (event: KeyboardEvent) => {
        const container = modalRef.value
        if (!container) return

        if (event.key === 'Escape') {
            event.preventDefault()
            onClose()
            return
        }

        if (event.key !== 'Tab') return

        const focusable = Array.from(container.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
            (el) => el.offsetParent !== null || el === document.activeElement,
        )
        if (focusable.length === 0) return

        const first = focusable[0]
        const last = focusable[focusable.length - 1]

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault()
            last.focus()
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault()
            first.focus()
        }
    }

    const stop = watch(isOpen, async (open) => {
        if (open) {
            previouslyFocused = document.activeElement
            document.addEventListener('keydown', onKeyDown)
            await nextTick()
            const container = modalRef.value
            // Prefer the first form field, so opening a modal puts the
            // keyboard straight into the form instead of on the X button.
            const firstField = container?.querySelector<HTMLElement>(
                'input:not([disabled]), select:not([disabled]), textarea:not([disabled])',
            )
            const first = firstField ?? container?.querySelector<HTMLElement>(FOCUSABLE)
            if (first) first.focus()
            else container?.focus()
        } else {
            document.removeEventListener('keydown', onKeyDown)
            if (previouslyFocused instanceof HTMLElement) previouslyFocused.focus()
            previouslyFocused = null
        }
    })

    onUnmounted(() => {
        document.removeEventListener('keydown', onKeyDown)
        stop()
    })
}
