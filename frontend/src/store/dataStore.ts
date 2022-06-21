import { defineStore } from "pinia";
import server from '@/store/apiStore'
export const dataStore = defineStore('dataStore', {
    state: () => ({
        members: <any>[],
        groups: <any>[],
        defaultGrp: { id: 0, label: 'None' },
        internetError: <boolean>false,
        currentGroup: <string>'',
        groupAddArray: <any>[]
    }),
    actions: {
        memberQuery(id: string) {
            let thisMember = this.members.find((x: { id: string; }) => x.id == id)
            let thisGrp = this.groups.find((x: { id: any; }) => x.id == thisMember.group_id)
            if (thisGrp == undefined) thisGrp = this.defaultGrp
            thisMember.group = thisGrp
            return thisMember
        },

        groupAddQuery() {
            let list = this.members.filter(x => x.group_id != this.currentGroup)
            list.forEach((x: { checked: boolean }) => {
                x.checked = false
            })
            this.groupAddArray = list;

        },

        async getMembers(orgId: string) {
            try {
                var { data } = await server.getMembers(orgId)
                if (data) {
                    let list = data.data
                    list.forEach((x: { checked: boolean }) => {
                        x.checked = false
                    })
                    this.members = list
                }
                this.internetError = false
            } catch (error) {
                console.log(error);
                this.internetError = true
            }
        },
        async getGroupNames(orgId: string) {
            try {
                var { data } = await server.getGroupNames(orgId)
                if (data) {
                    let grp = data.groups;
                    this.groups = grp.map((x: { id: string; group_name: string; }) => ({
                        id: x.id,
                        name: x.group_name,
                        label: x.group_name
                    }))
                }
            } catch (error) {
                console.log(error);
            }
        }
    }
})