import { defineStore } from "pinia";
import server from '@/store/apiStore'
export const dataStore = defineStore('dataStore', {
    state: () => ({
        members: <any>[],
        groups: <any>[],
        events: <any>[],
        defaultGrp: { id: 0, label: 'None' },
        internetError: <boolean>false,
        membersLoading: true,
        groupsLoading: true
    }),
    actions: {
        memberQuery(id: string) {
            let thisMember = this.members.find((x: { id: string; }) => x.id == id)
            let thisGrp = this.groups.find((x: { id: any; }) => x.id == thisMember.group_id)
            if (thisGrp == undefined) thisGrp = this.defaultGrp
            thisMember.group = thisGrp
            return thisMember
        },

        loadMaster() {
            this.getGroupNames()
            this.getMembers()
            this.getEvents()
        },

        async getMembers() {
            try {
                var { data } = await server.getMembers()
                if (data) {
                    let list = data.data
                    list.forEach((x: { checked: boolean }) => {
                        x.checked = false
                    })
                    this.members = list

                    this.internetError = false
                    this.membersLoading = false
                }
                else {
                    this.internetError = false
                    this.membersLoading = false
                }

            } catch (error) {
                console.log(error);
                this.membersLoading = false
                this.internetError = true
            }
        },
        async getGroupNames() {
            try {
                var { data } = await server.getGroupNames()
                if (data) {
                    let grp = data.groups;
                    grp.forEach((x: { name: any; group_name: any; label: any; }) => {
                        x.name = x.group_name;
                        x.label = x.group_name
                    })
                    this.groups = grp
                    // this.groups = grp.map((x: { id: string; group_name: string; created: string }) => ({
                    //     id: x.id,
                    //     created: x.created,
                    //     name: x.group_name,
                    //     label: x.group_name
                    // }))
                    this.internetError = false
                    this.groupsLoading = false
                }
                else {
                    this.internetError = false
                    this.groupsLoading = false
                }

            } catch (error) {
                this.internetError = true
                this.groupsLoading = false
                console.log(error);
            }
        },

        async getEvents() {
            try {
                var { data } = await server.getEvents()
                if (data) this.events = data

            } catch (error) {
                this.internetError = true
                console.log(error);
            }
        }
    }
})