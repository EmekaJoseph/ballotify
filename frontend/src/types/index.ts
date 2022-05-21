interface LoginFormInterface {
    username: string,
    password: string,
    err: {
        username: string
        password: string
    }
}


interface newUserInterface {
    firstname: string,
    lastname: string,
    email: string,
    password: string,
    rpassword: string,
    err: {
        firstname: string,
        lastname: string,
        email: string,
        password: string,
        rpassword: string,
    }
}

interface newOrgInterface {
    name: string,
    address: string,
    err: {
        name: string,
        address: string,
    }
}

interface newRegister {
    firstname: string,
    lastname: string,
    email: string,
    password: string,
    org_name: string,
    org_address: string
}


export { LoginFormInterface, newOrgInterface, newUserInterface, newRegister }