import { defineStore } from "pinia";
import { useRoute } from 'vue-router';
export const groudDetailStore = defineStore('groudDetailStore', {
    state: () => ({
        members: <any>[],
        groups: <any>[],
        group: {
            id: '',
            group_id: <any>'',
            name: '',
            created: '',
            members: <any>[]
        },
        route: useRoute(),
        membersAddList: [],

    }),
    getters: {

    },
    actions: {
        setData(groups: any, members: any) {
            this.members = members;
            this.groups = groups;
            let thisQuery = this.route.query.g
            let thisGroup = groups.find((x: { id: any }) => x.id == thisQuery)
            this.group.id = thisGroup.id;
            this.group.group_id = thisQuery;
            this.group.name = thisGroup.group_name;
            this.group.created = thisGroup.created;
            this.group.members = members.filter((x: { group_id: any }) => x.group_id == thisQuery);

        },

        membersAddQuery() {
            let list = this.members.filter((x: { group_id: any; }) => x.group_id != this.group.group_id)
            list.forEach((x: { checked: boolean }) => {
                x.checked = false
            })
            this.membersAddList = list;
        },

        fGroupName(id: string) {
            let grpObj = this.groups.find((x: { id: any; }) => x.id == id)
            return grpObj == undefined ? 'None' : grpObj.name
        }
    }
})