import { defineStore } from "pinia";
import server from '@/store/apiStore'
import { useRoute } from 'vue-router';
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
        route: useRoute(),
        votingMasterData: <any>[]

    }),
    actions: {
        async getEventDetails() {
            try {
                let Code: any = this.route.query.e
                let event_id = atob(Code)
                this.event.isLoading = true
                var { data } = await server.getEventDetails(event_id)
                this.event.id = data.id
                this.event.event_id = data.event_id
                this.event.name = data.event_name
                this.event.start = new Date(data.event_start)
                this.event.end = new Date(data.event_expiry)
                this.event.type = data.event_type
                this.event.desc = data.event_description
                this.event.created = data.created
                // await this.getPositions()
                // await this.getCandidates()
                await this.getVoters()
                await this.votingMasterQuery()
                this.event.isLoading = false
            } catch (error) {
                // console.log(error);
                this.event.isLoading = false
                this.event.error = true
            }
        },
        async getPositions() {
            try {
                var { data } = await server.getPositions(this.event.event_id)
                // console.log(data);
                this.positions = data.positions
            } catch (error) {
                // console.log(error);
            }
        },

        async getCandidates() {
            try {
                this.candidates = []
                var { data } = await server.getCandidates(this.event.event_id)
                console.log(data);
                this.candidates = data.candidates
            } catch (error) {
                // console.log(error);
            }
        },

        async getVoters() {
            try {
                this.voters = []
                var { data } = await server.getVoters(this.event.event_id)
                this.voters = data.voters
            } catch (error) {
                // console.log(error);
            }
        },

        async votingMasterQuery() {
            try {
                this.voters = []
                var { data } = await server.votingMasterQuery(this.event.event_id)
                console.log(data);

                this.votingMasterData = data.vote_data
            } catch (error) {
                // console.log(error);
            }
        },
    }
})