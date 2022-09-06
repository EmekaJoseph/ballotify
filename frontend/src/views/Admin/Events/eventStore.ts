import { defineStore } from "pinia";
import server from '@/store/apiStore'
import { useRoute } from 'vue-router';
export const eventStore = defineStore('eventStore', {
    state: () => ({
        event: {
            id: '',
            event_id: '',
            name: '',
            start: new Date(),
            end: new Date(),
            type: '',
            created: '',
            desc: '',
            isLoading: true
        },
        positions: <any>[],
        candidates: <any>[],
        voters: <any>[],
        event_id: useRoute()

    }),
    actions: {
        async getEventDetails() {
            try {
                this.event.isLoading = true

                var { data } = await server.getEventDetails(this.event_id.query.id)
                this.event.id = data.id
                this.event.event_id = data.event_id
                this.event.name = data.event_name
                this.event.start = new Date(data.event_start)
                this.event.end = new Date(data.event_expiry)
                this.event.type = data.event_type
                this.event.desc = data.event_description
                this.event.created = data.created
                this.getPositions()
                this.getCandidates()
                this.event.isLoading = false
            } catch (error) {
                console.log(error);
            }
        },
        async getPositions() {
            try {
                var { data } = await server.getPositions(this.event_id.query.id)
                this.positions = data.positions
            } catch (error) {
                console.log(error);
            }
        },

        async getCandidates() {
            try {
                var { data } = await server.getCandidates(this.event_id.query.id)
                this.candidates = data.candidates
            } catch (error) {
                console.log(error);
            }
        },
    }
})