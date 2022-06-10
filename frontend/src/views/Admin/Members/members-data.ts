import { defineStore } from "pinia";
import server from '@/store/apiStore'
export const membersData = defineStore('membersData', {
    state: () => ({
        members: <any>[],
        groups: <any>[],
        defaultGrp: { id: 0, label: 'None' }
    }),
    actions: {
        memberQuery(id: string) {
            let thisMember = this.members.find((x: { id: string; }) => x.id == id)
            let thisGrp = this.groups.find(x => x.id == thisMember.group_id)
            if (thisGrp == undefined) thisGrp = this.defaultGrp
            thisMember.group = thisGrp
            return thisMember
        },

        async getMembers(orgId: string) {
            try {
                var { data } = await server.getMembers(orgId)
                if (data) {
                    let data1 = data.data
                    data1.forEach((x: { checked: boolean }) => {
                        x.checked = false
                    })
                    this.members = data1
                }
            } catch (error) {
                console.log(error);
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