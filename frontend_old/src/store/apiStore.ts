import axios from 'axios'
import { adminAccount } from '@/store/admin/account'
import { storeToRefs } from 'pinia'

// const mainURL = 'http://localhost'
const mainURL = 'http://localhost:8080'

const bus = axios.create({
    baseURL: mainURL,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json;charset=UTF-8;text/json',
    },
})

const busImage = axios.create({
    baseURL: mainURL,
    headers: {
        "Content-Type": "multipart/form-data;",
        // "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
    }
})

const admin = adminAccount()
const { thisOrgId } = storeToRefs(admin)



export default {
    // overview
    getOverview() {
        return bus.get('/getOverview/' + thisOrgId.value)
    },



    // checks
    checkIfEmailExists(email: string) {
        return bus.get('/checkIfEmailExists/' + email)
    },
    checkIfOrgExists(name: string) {
        return bus.get('/checkIfOrgExists/' + name)
    },



    // init
    registerNew(obj: string) {
        return bus.get('/registerNew/' + obj)
    },
    login(obj: any) {
        let encoded = window.btoa(JSON.stringify(obj))
        return bus.get('/login/' + encoded)
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
        return bus.get('/getOrgDetails/' + thisOrgId.value)
    },
    updateOrg(obj: object) {
        return bus.post('/updateOrg', obj)
    },



    // groups
    getGroupNames() {
        return bus.get('groups/' + thisOrgId.value)
    },
    saveNewGroup(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/groups', obj)
    },
    deleteGroup(group_id: any) {
        return bus.delete('/groups/' + group_id)
    },
    renameGroup(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.put('/groups/' + obj.id, obj)
    },




    // members
    saveNewMember(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/members', obj)
    },
    getMembers() {
        return bus.get('/members/' + thisOrgId.value)
    },
    deleteMember(id: any) {
        return bus.delete('/members/' + id)
    },
    updateMember(obj: any) {
        return bus.put('/members/' + obj.id, obj)
    },

    updateMembersGroup(obj: any) {
        return bus.get('/updateMembersGroup/' + obj)
    },



    // events
    saveNewEvent(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/events', obj)
    },

    getEvents() {
        return bus.get('/events/' + thisOrgId.value)
    },

    getEventDetails(event_id: any) {
        return bus.get('/getEventDetails/' + event_id)
    },

    updateEvent(obj: any) {
        return bus.put('/events/' + obj.id, obj)
    },


    // voting
    saveNewPosition(obj: any) {
        obj.org_id = thisOrgId.value
        return bus.post('/saveNewPosition', JSON.stringify(obj))
    },

    getPositions(event_id: any) {
        return bus.get('/getPositions/' + event_id)
    },

    removePosition(id: any) {
        return bus.get('/removePosition/' + id)
    },

    saveCandidate(formData: any) {
        return bus.post('/saveCandidate', formData)
    },

    getCandidates(event_id: any) {
        return bus.get('/getCandidates/' + event_id)
    },

    removeCandidate(id: any) {
        return bus.get('/removeCandidate/' + id)
    },

    saveVoter(formData: any) {
        return bus.post('/saveVoter', formData)
    },

    getVoters(event_id: any) {
        return bus.get('/getVoters/' + event_id)
    },

    removeVoter(id: any) {
        return bus.get('/removeVoter/' + id)
    },


    votingDataQuery(event_id: any) {
        return bus.get('/votingDataQuery/' + event_id)
    },

    checkVotingCode(code: string) {
        return bus.get('/checkVotingCode/' + code)
    },

    submitVote(obj: object) {
        return bus.post('/submitVote', JSON.stringify(obj))
    }
}