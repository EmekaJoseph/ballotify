import axios from 'axios'
import { adminAccount } from '@/store/admin/account'
import { storeToRefs } from 'pinia'

const bus = axios.create({
    baseURL: 'http://localhost',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
})

const busImage = axios.create({
    baseURL: 'http://localhost',
    headers: {
        "Content-Type": "multipart/form-data",
        "X-Requested-With": "XMLHttpRequest",
    }
})

const admin = adminAccount()
const { thisOrgId } = storeToRefs(admin)



export default {
    // overview
    getOverview() {
        return bus.post('/getOverview/' + thisOrgId.value)
    },



    // checks
    checkIfEmailExists(email: string) {
        return bus.post('/checkIfEmailExists/' + email)
    },
    checkIfOrgExists(name: string) {
        return bus.post('/checkIfOrgExists/' + name)
    },



    // init
    registerNew(obj: string) {
        return bus.post('/registerNew/' + obj)
    },
    login(obj: object) {
        return bus.post('/login', obj)
    },




    //user/admin
    getUserDetails(id: string) {
        let data: object = { id: id, org_id: thisOrgId.value }
        return bus.post('/getUserDetails', data)
    },
    updateUser(obj: object) {
        return bus.post('/updateUser', obj)
    },



    // org
    getOrgDetails() {
        return bus.post('/getOrgDetails/' + thisOrgId.value)
    },
    updateOrg(obj: object) {
        return bus.post('/updateOrg', obj)
    },



    // groups
    getGroupNames() {
        return bus.post('/getGroupNames/' + thisOrgId.value)
    },
    saveNewGroup(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/saveNewGroup', obj)
    },
    deleteGroup(group_id: any) {
        let data: object = { id: group_id, org_id: thisOrgId.value }
        return bus.post('/deleteGroup', data)
    },
    renameGroup(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/renameGroup', obj)
    },




    // members
    saveNewMember(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/saveNewMember', obj)
    },
    getMembers() {
        return bus.post('/getMembers/' + thisOrgId.value)
    },
    deleteMember(id: any) {
        let data: object = { id: id, org_id: thisOrgId.value }
        return bus.post('/deleteMember', data)
    },
    updateMember(obj: any) {
        return bus.post('/updateMember', obj)
    },

    updateMembersGroup(obj: any) {
        return bus.post('/updateMembersGroup/' + obj)
    },



    // events
    saveNewEvent(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/saveNewEvent', JSON.stringify(obj))
    },

    getEvents() {
        return bus.post('/getEvents/' + thisOrgId.value)
    },

    getEventDetails(event_id: any) {
        return bus.post('/getEventDetails/' + event_id)
    },


    // voting
    saveNewPosition(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/saveNewPosition', JSON.stringify(obj))
    },

    getPositions(event_id: any) {
        return bus.post('/getPositions/' + event_id)
    },

    removePosition(id: any) {
        return bus.post('/removePosition/' + id)
    },

    saveCandidate(formData: any) {
        formData.append("org_id", thisOrgId.value);
        return busImage.post('/saveCandidate', formData)
    }
}