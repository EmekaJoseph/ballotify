import { defineStore } from "pinia";

export const useAdminStore = defineStore({
    id: "admin",
    state: () => ({
        localStore: '',
        signedIn: (localStorage.getItem('ballotify-admin') == null) ? false : true,
        data: localStorage.getItem('ballotify-admin')
    }),
    getters: {
        hasAccess: (state) => (state.signedIn) ? true : false,
        getData: (state) => (state.data == null) ? {} : JSON.parse(state.data)
    },
    actions: {
        signIn(obj: object) {
            localStorage.setItem('ballotify-admin', JSON.stringify(obj))
            this.signedIn = true
        },
        signOut() {
            localStorage.removeItem('ballotify-admin')
            this.signedIn = false
        },

    },
});
