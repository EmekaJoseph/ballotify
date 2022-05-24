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
    }
}