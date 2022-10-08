import { defineStore } from "pinia";
import server from '@/store/apiStore'
export const voteStore = defineStore('voteStore', {
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
            isLoading: true,
            error: false
        },
        positions: <any>[],
        candidates: <any>[],
        voters: <any>[],

    }),
    actions: {
        async getEventDetails(id) {
            try {
                this.event.isLoading = true
                var { data } = await server.getEventDetails(id)
                this.event.id = data.id
                this.event.event_id = data.event_id
                this.event.name = data.event_name
                this.event.start = new Date(data.event_start)
                this.event.end = new Date(data.event_expiry)
                this.event.type = data.event_type
                this.event.desc = data.event_description
                this.event.created = data.created
                await this.getPositions(id)
                await this.getCandidates(id)
                await this.getVoters(id)
                this.event.isLoading = false
            } catch (error) {
                // console.log(error);
                this.event.isLoading = false
                this.event.error = true
            }
        },
        async getPositions(id) {
            try {
                var { data } = await server.getPositions(id)
                this.positions = data.positions
            } catch (error) {
                // console.log(error);
            }
        },

        async getCandidates(id) {
            try {
                var { data } = await server.getCandidates(id)
                console.log(data);
                this.candidates = data.candidates
            } catch (error) {
                // console.log(error);
            }
        },

        async getVoters(id) {
            try {
                var { data } = await server.getVoters(id)
                console.log(data);

                this.voters = data.voters
            } catch (error) {
                // console.log(error);
            }
        },
    }
})