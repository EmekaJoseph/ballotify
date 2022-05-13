interface LoginFormInterface {
    username: string,
    password: string,
    err: {
        username: string
        password: string
    }
}


export { LoginFormInterface }