import { defineStore } from "pinia";

export const useColorStore = defineStore({
    id: "colorStore",
    state: () => ({
        cc1: '#03787c',
        cc2: '#04C3C9',
        ccThk: '#026063',
        ccBg: '#03787c11',
        ccBtnH: '#03858A'

    })
});
