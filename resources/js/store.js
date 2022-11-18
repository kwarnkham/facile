// store.js
import { reactive } from 'vue'

export const store = reactive({
    cart: {
        items: JSON.parse(localStorage.getItem('cartItems')) ?? [],
        discount: 0,
        add (feature, quantity = 1) {
            feature = JSON.parse(JSON.stringify(feature))
            feature.quantity = Number(quantity);
            const existed = this.items.findIndex(e => e.id == feature.id);
            if (existed == -1) this.items.push(feature)
            else this.items[existed].quantity += feature.quantity
            localStorage.setItem('cartItems', JSON.stringify(this.items))
        },
        remove (feature, quantity = 1) {
            quantity = Number(quantity)
            feature = JSON.parse(JSON.stringify(feature))
            const existed = this.items.findIndex(e => e.id == feature.id);
            if (existed == -1) return
            else {
                if (this.items[existed].quantity > quantity) this.items[existed].quantity -= quantity
                else this.items.splice(existed, 1)
            }
            localStorage.setItem('cartItems', JSON.stringify(this.items))
        },
        update (feature) {
            feature = JSON.parse(JSON.stringify(feature))
            const index = this.items.findIndex(e => e.id == feature.id);
            this.items[index] = feature
            if (this.items[index].quantity <= 0)
                this.items.splice(index, 1)
            localStorage.setItem('cartItems', JSON.stringify(this.items))
        },
        clear () {
            this.items = []
            localStorage.setItem('cartItems', JSON.stringify(this.items))
        }
    }
})
