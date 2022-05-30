import axios from 'axios'

const bus = axios.create({
    baseURL: 'http://localhost',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
})


export default {
    checkIfEmailExists(email: string) {
        return bus.post('/checkIfEmailExists/' + email)
    },

    checkIfOrgExists(name: string) {
        return bus.post('/checkIfOrgExists/' + name)
    },

    registerNew(obj: string) {
        return bus.post('/registerNew/' + obj)
    },
    login(obj: object) {
        return bus.post('/login', obj)
    },
    getOrgDetails(id: string | number) {
        return bus.post('/getOrgDetails/' + id)
    },
    getUserDetails(id: string, org_id: string) {
        let data: object = { id: id, org_id: org_id }
        return bus.post('/getUserDetails', data)
    },
    updateOrg(obj: object) {
        return bus.post('/updateOrg', obj)
    },
    updateUser(obj: object) {
        return bus.post('/updateUser', obj)
    }
}