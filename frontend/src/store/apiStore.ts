import axios from 'axios'
import { adminAccount } from '@/store/admin/account'

const bus = axios.create({
    baseURL: 'http://localhost',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
})
const thisOrgId = adminAccount().getData.org_id


export default {

    // overview
    getOverview() {
        return bus.post('/getOverview/' + thisOrgId)
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
        let data: object = { id: id, org_id: thisOrgId }
        return bus.post('/getUserDetails', data)
    },
    updateUser(obj: object) {
        return bus.post('/updateUser', obj)
    },



    // org
    getOrgDetails() {
        return bus.post('/getOrgDetails/' + thisOrgId)
    },
    updateOrg(obj: object) {
        return bus.post('/updateOrg', obj)
    },



    // groups
    getGroupNames() {
        return bus.post('/getGroupNames/' + thisOrgId)
    },
    saveNewGroup(obj: any) {
        obj.org_id = thisOrgId
        return bus.post('/saveNewGroup', obj)
    },
    deleteGroup(group_id: any) {
        let data: object = { id: group_id, org_id: thisOrgId }
        return bus.post('/deleteGroup', data)
    },
    renameGroup(obj: any) {
        obj.org_id = thisOrgId
        return bus.post('/renameGroup', obj)
    },




    // members
    saveNewMember(obj: any) {
        obj.org_id = thisOrgId
        return bus.post('/saveNewMember', obj)
    },
    getMembers() {
        return bus.post('/getMembers/' + thisOrgId)
    },
    deleteMember(id: any) {
        let data: object = { id: id, org_id: thisOrgId }
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
        obj.org_id = thisOrgId
        return bus.post('/saveNewEvent', JSON.stringify(obj))
    },
}