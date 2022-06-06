import { defineStore } from "pinia";

export const useAdminStore = defineStore('admin', {
    // id: "admin",
    state: () => ({
        localStore: '',
        signedIn: (localStorage.getItem('ballotify-admin') == null) ? false : true,
        data: localStorage.getItem('ballotify-admin'),
        fullName: localStorage.getItem('b-fname')
    }),
    getters: {
        hasAccess: (state) => (state.signedIn) ? true : false,
        getData: (state) => (state.data == null) ? {} : JSON.parse(state.data),
    },
    actions: {
        signIn(obj: object) {
            this.setState(obj)
            this.signedIn = true
        },
        setState(obj: any) {
            localStorage.setItem('ballotify-admin', JSON.stringify(obj))
            this.setFullName(obj.name)
            this.data = localStorage.getItem('ballotify-admin')
        },
        setFullName(name: string) {
            localStorage.setItem('b-fname', name)
            this.fullName = name
        },
        signOut() {
            localStorage.removeItem('ballotify-admin')
            this.signedIn = false
        },

    },
});
