import { reactive } from 'vue'
import { newUserInterface, newOrgInterface } from '@/types'
import server from '@/store/apiStore'

const state = reactive({
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
    },

    loading: false,
    regSuccess: false

})

const methods = {
    resetError() {
        state.user.err.firstname = ''
        state.user.err.lastname = ''
        state.user.err.email = ''
        state.user.err.password = ''
        state.user.err.rpassword = ''
        state.org.err.name = ''
        state.org.err.address = ''
    },

    resetData() {
        state.user.firstname = ''
        state.user.lastname = ''
        state.user.email = ''
        state.user.password = ''
        state.user.rpassword = ''
        state.org.name = ''
        state.org.address = ''
    },

    testEmail: (email: string) => {
        var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email)
    },

    testPassword: (password: string) => {
        var regex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).{8,}$/;
        return regex.test(password)
    },

    emailExists: async (email: string) => {
        state.loading = true
        try {
            var { data } = await server.checkIfEmailExists(email);
            // console.log(data);

            var val = (data == true) ? true : (data == false ? false : null);
            state.loading = false
        } catch (error) {
            val = null;
            state.loading = false
        }
        return val
    },

    orgExists: async (name: string) => {
        state.loading = true
        try {
            var { data } = await server.checkIfOrgExists(name);
            var val = (data == true) ? true : (data == false ? false : null)
            state.loading = false
        } catch (error) {
            val = null;
            state.loading = false
        }
        return val
    }
}

export default {
    state, methods
}