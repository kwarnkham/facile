// store.js
import { reactive } from 'vue'

export const store = reactive({
    cart: JSON.parse(localStorage.getItem('cart')) ?? [],
    addToCart (feature, quantity = 1) {
        feature = JSON.parse(JSON.stringify(feature))
        feature.quantity = Number(quantity);
        const existed = this.cart.findIndex(e => e.id == feature.id);
        if (existed == -1) this.cart.push(feature)
        else this.cart[existed].quantity += feature.quantity
        localStorage.setItem('cart', JSON.stringify(this.cart))
    },
    removeFromCart (feature, quantity = 1) {
        quantity = Number(quantity)
        feature = JSON.parse(JSON.stringify(feature))
        const existed = this.cart.findIndex(e => e.id == feature.id);
        if (existed == -1) return
        else {
            if (this.cart[existed].quantity > quantity) this.cart[existed].quantity -= quantity
            else this.cart.splice(existed, 1)
        }
        localStorage.setItem('cart', JSON.stringify(this.cart))
    },
    updateCart (feature) {
        feature = JSON.parse(JSON.stringify(feature))
        const index = this.cart.findIndex(e => e.id == feature.id);
        this.cart[index] = feature
        if (this.cart[index].quantity <= 0)
            this.cart.splice(index, 1)
        localStorage.setItem('cart', JSON.stringify(this.cart))
    },
    clearCart () {
        this.cart = []
        localStorage.setItem('cart', JSON.stringify(this.cart))
    }
})
