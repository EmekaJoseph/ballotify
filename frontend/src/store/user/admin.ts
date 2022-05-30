import { defineStore } from "pinia";

export const useAdminStore = defineStore({
    id: "admin",
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
            this.setData(obj)
            this.signedIn = true
        },
        setData(obj: any) {
            localStorage.setItem('ballotify-admin', JSON.stringify(obj))
            localStorage.setItem('b-fname', obj.name)
            this.data = localStorage.getItem('ballotify-admin')
            this.fullName = obj.name
        },
        signOut() {
            localStorage.removeItem('ballotify-admin')
            this.signedIn = false
        },

    },
});
