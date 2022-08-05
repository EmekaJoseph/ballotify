import { defineStore } from "pinia";

export const adminAccount = defineStore('adminAccount', {
    // id: "admin",
    state: () => ({
        signedIn: (localStorage.getItem('ballotify') == null) ? false : true,
        data: <any>localStorage.getItem('ballotify'),
        _user: '',
    }),
    getters: {
        hasAccess: (state) => state.signedIn,
        getData: (state) => JSON.parse(atob(state.data)),
        fullName: (state) => (JSON.parse(atob(state.data))).user_name,
        orgName: (state) => (JSON.parse(atob(state.data))).org_name,
        thisOrgId: (state) => (JSON.parse(atob(state.data))).org_id
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
            this.setNames(obj.user_name, obj.org_name)
        },
        setNames(user_name: string, org?: string) {
            let storedData: any = localStorage.getItem('ballotify')
            let xData = JSON.parse(atob(storedData))
            this._user = xData.user_name = user_name
            //  = user_name
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
