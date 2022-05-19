import { reactive } from 'vue'
import { newUserInterface, newOrgInterface } from '@/types'

const data = reactive({
    user: <newUserInterface>{
        firstname: '',
        lastname: '',
        email: '',
        password: '',
        rpassword: '',
        err: {
            firstname: '',
            lastname: '',
            email: '',
            password: '',
            rpassword: '',
        },
    },

    org: <newOrgInterface>{
        name: '',
        address: '',
        err: {
            name: '',
            address: '',
        },
    }

})

const methods = {
    resetError() {
        data.user.err.firstname = ''
        data.user.err.lastname = ''
        data.user.err.email = ''
        data.user.err.password = ''
        data.user.err.rpassword = ''
        data.org.err.name = ''
        data.org.err.address = ''
    }
}

export default {
    data, methods
}