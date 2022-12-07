import { inject } from "vue"

export default function useConfirm () {
    const { updateOpenConfirmDialog, updateConfirmInvoke } = inject("confirmDialog")

    const confirm = (callback) => {
        updateOpenConfirmDialog(true)
        updateConfirmInvoke(callback)
    }
    return {
        confirm
    }
}
