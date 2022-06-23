import { defineStore } from "pinia";

export const useAdminStore = defineStore('admin', {
    // id: "admin",
    state: () => ({
        localStore: '',
        signedIn: (localStorage.getItem('ballotify-admin') == null) ? false : true,
        data: localStorage.getItem('ballotify-admin'),
        fullName: localStorage.getItem('b-fname'),
        orgName: localStorage.getItem('b-orgname')
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
            this.setNames(obj.name, obj.org_name)
            this.data = localStorage.getItem('ballotify-admin')
        },
        setNames(name: string, org: string) {
            localStorage.setItem('b-fname', name)
            localStorage.setItem('b-orgname', org)
            this.fullName = name
            this.orgName = org
        },
        signOut() {
            localStorage.removeItem('ballotify-admin')
            this.signedIn = false
        },

    },
});
