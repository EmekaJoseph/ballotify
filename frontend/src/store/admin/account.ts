import { defineStore } from "pinia";

export const adminAccount = defineStore('adminAccount', {
    // id: "admin",
    state: () => ({
        signedIn: (localStorage.getItem('ballotify') == null) ? false : true,
        data: localStorage.getItem('ballotify'),
        current_user: ''
    }),
    getters: {
        hasAccess: (state) => state.signedIn,
        getData: (state) => (state.data == null) ? {} : JSON.parse(atob(state.data)),
        fullName: (state) => (state.data == null) ? '' : (JSON.parse(atob(state.data))).name,
        orgName: (state) => (state.data == null) ? '' : (JSON.parse(atob(state.data))).org_name,
    },
    actions: {
        signIn(obj: object) {
            this.setState(obj)
            this.signedIn = true
        },
        setState(obj: any) {
            let base64Decoded: any = btoa(JSON.stringify(obj))
            localStorage.setItem('ballotify', base64Decoded)
            this.data = base64Decoded
            this.setNames(obj.name, obj.org_name)
        },
        setNames(name: string, org?: string) {
            let storedData: any = localStorage.getItem('ballotify')
            let xData = JSON.parse(atob(storedData))
            xData.name = name
            this.current_user = name
            if (org) {
                xData.org_name = org
            }
            localStorage.setItem('ballotify', btoa(JSON.stringify(xData)))
        },
        signOut() {
            localStorage.removeItem('ballotify')
            this.signedIn = false
        },

    },
});
