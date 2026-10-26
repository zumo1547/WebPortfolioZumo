import { useEffect } from 'react'

const protectedSelector = '.content-protected'
const editableSelector = 'input, textarea, select, [contenteditable="true"], [role="textbox"]'

function elementFromTarget(target: EventTarget | null): Element | null {
  if (target instanceof Element) return target
  if (target instanceof Node) return target.parentElement
  return null
}

function isEditable(target: EventTarget | null): boolean {
  return Boolean(elementFromTarget(target)?.closest(editableSelector))
}

function isProtected(target: EventTarget | null): boolean {
  return Boolean(elementFromTarget(target)?.closest(protectedSelector))
}

function selectionIsProtected(): boolean {
  const selection = window.getSelection()
  return Boolean(selection && !selection.isCollapsed && (
    isProtected(selection.anchorNode) || isProtected(selection.focusNode)
  ))
}

export function ContentProtection() {
  useEffect(() => {
    // Browser-side deterrence only: visible assets and screenshots cannot be made private here.
    const blockClipboard = (event: ClipboardEvent) => {
      if (!isEditable(event.target) && (isProtected(event.target) || selectionIsProtected())) {
        event.preventDefault()
      }
    }

    const blockContextMenu = (event: MouseEvent) => {
      if (isProtected(event.target) && !isEditable(event.target)) event.preventDefault()
    }

    const blockImageDrag = (event: DragEvent) => {
      const target = elementFromTarget(event.target)
      if (target?.closest('img') && isProtected(target)) event.preventDefault()
    }

    document.addEventListener('copy', blockClipboard, true)
    document.addEventListener('cut', blockClipboard, true)
    document.addEventListener('contextmenu', blockContextMenu, true)
    document.addEventListener('dragstart', blockImageDrag, true)

    return () => {
      document.removeEventListener('copy', blockClipboard, true)
      document.removeEventListener('cut', blockClipboard, true)
      document.removeEventListener('contextmenu', blockContextMenu, true)
      document.removeEventListener('dragstart', blockImageDrag, true)
    }
  }, [])

  return null
}
