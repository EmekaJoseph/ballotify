import { reactive } from 'vue'

const x = reactive({
    spell(name: string, rate: number): string {
        let str: string = name;
        if (rate > 1 || rate == 0) {
            str = name + 's'
        }
        return str
    }
})

export default {
    x
}