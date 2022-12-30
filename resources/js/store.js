// store.js
import { reactive } from 'vue'

export const store = reactive({
    cart: {
        items: JSON.parse(localStorage.getItem('cartItems')) ?? [],
        discount: 0,
        toppings: JSON.parse(localStorage.getItem('toppings')) ?? [],
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
            this.toppings = []
            this.discount = 0;
            localStorage.setItem('cartItems', JSON.stringify(this.items))
            localStorage.setItem('toppings', JSON.stringify(this.toppings))
            localStorage.setItem('discount', this.discount)
        },
        addTopping (topping, quantity = 1) {
            topping = JSON.parse(JSON.stringify(topping))
            topping.quantity = Number(quantity);
            const existed = this.toppings.findIndex(e => e.id == topping.id);
            if (existed == -1) this.toppings.push(topping)
            else this.toppings[existed].quantity += topping.quantity
            localStorage.setItem('toppings', JSON.stringify(this.toppings))
        },
        updateTopping (topping) {
            topping = JSON.parse(JSON.stringify(topping))
            const index = this.toppings.findIndex(e => e.id == topping.id);
            this.toppings[index] = topping
            if (this.toppings[index].quantity <= 0)
                this.toppings.splice(index, 1)
            localStorage.setItem('toppings', JSON.stringify(this.toppings))
        }
    }
})
