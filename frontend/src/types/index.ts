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

interface newMemberInterface {
    firstname: string,
    lastname: string,
    email: string,
    phone: string,
    birthday: Date,
    gender: string,
    group: any,
    err: {
        firstname: string,
        lastname: string,
        email: string,
        phone: string,
        birthday: string,
        gender: string,
        group: string,
    }
}


export { LoginFormInterface, newOrgInterface, newUserInterface, newRegister, newMemberInterface }