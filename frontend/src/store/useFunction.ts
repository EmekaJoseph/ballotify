import { reactive } from 'vue'

const fx = reactive({
    spell(name: string, rate: number): string {
        let str: string = name;
        if (rate > 1 || rate == 0) {
            str = name + 's'
        }
        return str
    },
    monthStr(date: Date) {
        const array = [
            'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
        ]
        return array[date.getMonth()]
    }
})

export default {
    fx
}