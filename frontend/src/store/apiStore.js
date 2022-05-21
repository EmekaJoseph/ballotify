import axios from 'axios'

const bus = axios.create({
    baseURL: 'http://localhost',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    },
})


export default {
    testApi(name) {
        return bus.get('/testApi/' + name)
    },

    checkIfEmailExists(email) {
        return bus.post('/checkIfEmailExists/' + email)
    },

    checkIfOrgExists(name) {
        return bus.post('/checkIfOrgExists/' + name)
    },

    registerNew(obj) {
        return bus.post('/registerNew/' + obj)
    }
}