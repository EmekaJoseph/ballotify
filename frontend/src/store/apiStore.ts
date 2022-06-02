import axios from 'axios'

const bus = axios.create({
    baseURL: 'http://localhost',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
})


export default {
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
    getUserDetails(id: string, org_id: string) {
        let data: object = { id: id, org_id: org_id }
        return bus.post('/getUserDetails', data)
    },
    updateUser(obj: object) {
        return bus.post('/updateUser', obj)
    },


    // org
    getOrgDetails(id: string) {
        return bus.post('/getOrgDetails/' + id)
    },
    updateOrg(obj: object) {
        return bus.post('/updateOrg', obj)
    },



    // groups
    getGroupNames(org_id: string) {
        return bus.post('/getGroupNames/' + org_id)
    },
    saveNewGroup(obj: object) {
        return bus.post('/saveNewGroup', obj)
    },
    getGroupDetails(org_id: string, id: any) {
        let data: object = { id: id, org_id: org_id }
        return bus.post('/getGroupDetails', data)
    }
}