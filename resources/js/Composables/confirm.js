import { inject } from "vue"

export default function useConfirm () {
    const { updateOpenConfirmDialog, updateConfirmInvoke, updateConfirmTitle } = inject("confirmDialog")

    const confirm = (callback, title) => {
        if (title) updateConfirmTitle(title)
        updateOpenConfirmDialog(true)
        updateConfirmInvoke(callback)
    }
    return {
        confirm
    }
}
