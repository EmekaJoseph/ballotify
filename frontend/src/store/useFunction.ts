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
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ]
        return array[date.getMonth()]
    },
    birthdayStr: (date: Date) => {
        const day = date.getDate();
        const month = date.getMonth() + 1
        return `${month}-${day}`;
    },
    displayBD: (dateStr: String) => {
        const date = new Date(dateStr + '-2022')
        const day = date.getDate();
        const month = fx.monthStr(date)
        return `${month} ${day}`;
    },
    emailIsOk: (email: string) => {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email)
    },
})

export default {
    fx
}