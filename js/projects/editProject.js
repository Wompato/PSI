const extractUserIDs = (serializedArray) => {
    if (!serializedArray || serializedArray.length === 0) {
        return [];
    }
    // Remove escaping slashes
    const unescapedString = serializedArray[0].replace(/\\/g, '');
    // Extract all user IDs using regex
    const matches = unescapedString.match(/"(\d+)"/g);
    if (matches && matches.length > 0) {
        // Convert user ID strings to integers
        return matches.map(match => parseInt(match.match(/\d+/)[0], 10));
    }
    return [];
};

originalTeam = [];

function getPsiPersonnel() {
    const selectElement = jQuery('#input_8_1');

    const currentUrl = window.location.href;
    const queryString = currentUrl.split('?')[1];
    const urlSearchParams = new URLSearchParams(queryString);

    if (urlSearchParams.has('project-name')) {
        const projectName = urlSearchParams.get('project-name');
        jQuery.ajax({
            url: '/wp-json/psi/v1/project/' + projectName,
            method: 'GET',
            success: function(response) {
                getUsers()
                    .then(options => {
                        const personnelData = [
                            { array: response.meta_data.co_principal_investigator, role: 'Co-Principal Investigator' },
                            { array: response.meta_data.collaborators, role: 'Collaborator' },
                            { array: response.meta_data["co-investigators"], role: 'Co-Investigator' },
                            { array: response.meta_data.support, role: 'Support' },
                            { array: response.meta_data.graduate_student, role: 'Graduate Student' },
                            { array: response.meta_data.postdoctoral_associate, role: 'Postdoctoral Associate' },
                            { array: response.meta_data.science_pi, role: 'Science PI'}
                        ];

                        const rootEl = document.querySelector('.other_psi_personnel');
                        UserList.clearInstances();

                        let total = 0;
                        personnelData.forEach(data => {
                            const users = extractUserIDs(data.array);
                            total += users.length;
                            users.forEach(user => {
                                const userList = new UserList(options);
                                userList.setUser(user.toString());
                                userList.setRole(data.role);
                                originalTeam.push(user);
                                rootEl.appendChild(userList.listParent);
                            });
                        });

                        if (total === 0) {
                            const userList = new UserList(options);
                            rootEl.appendChild(userList.listParent);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching users:', error);
                    });
            },
            error: function(error) {
                console.error('API Error:', error);
            }
        });
    }

    selectElement.on('change', function() {
        const selectedValue = jQuery(this).val();
        originalTeam = [];
        UserList.clearInstances();

        jQuery.ajax({
            url: '/wp-json/psi/v1/project/' + selectedValue,
            method: 'GET',
            success: function(response) {
                getUsers()
                    .then(options => {
                        masterOptions = options;
                        const personnelData = [
                            { array: response.meta_data.co_principal_investigator, role: 'Co-Principal Investigator' },
                            { array: response.meta_data.collaborators, role: 'Collaborator' },
                            { array: response.meta_data["co-investigators"], role: 'Co-Investigator' },
                            { array: response.meta_data.support, role: 'Support' },
                            { array: response.meta_data.graduate_student, role: 'Graduate Student' },
                            { array: response.meta_data.postdoctoral_associate, role: 'Postdoctoral Associate' },
                            { array: response.meta_data.science_pi, role: 'Science PI'}
                        ];

                        const rootEl = document.querySelector('.other_psi_personnel');
                        UserList.clearInstances();

                        let total = 0;
                        personnelData.forEach(data => {
                            const users = extractUserIDs(data.array);
                            total += users.length;
                            users.forEach(user => {
                                const userList = new UserList(options);
                                userList.setUser(user.toString());
                                userList.setRole(data.role);
                                originalTeam.push(user);
                                rootEl.appendChild(userList.listParent);
                            });
                        });

                        if (total === 0) {
                            const userList = new UserList(masterOptions);
                            rootEl.appendChild(userList.listParent);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching users:', error);
                    });
            },
            error: function(error) {
                console.error('API Error:', error);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    getPsiPersonnel();
});

let masterOptions = [];

function getUsers() {
    return new Promise((resolve, reject) => {
        jQuery.ajax({
            url: '/wp-json/psi/v1/users',
            method: 'GET',
            success: function(response) {
                let options = [];

                // Iterate over the staff_members array
                response.staff_members.forEach(user => {
                    // Push an object to the options array for each user
                    options.push({
                        value: user.data.ID.toString(), // Convert user ID to string
                        text: user.data.display_name // Use the user's display name as text
                    });
                });

                // Resolve the promise with the options array
                resolve(options);
            },
            error: function(xhr, status, error) {
                // Reject the promise with the error
                reject(error);
            }
        });
    });
}

class UserList {
    static instanceCount = 0;
    static instances = [];

    constructor(options) {
        this.options = options;
        this.listParent = this.createListParentElement();
        this.userSelect = this.createUserSelectElement();
        this.roleSelect = this.createRoleSelectElement();
        this.addButton = this.createAddButton();

        this.listParent.append(this.userSelect, this.roleSelect, this.addButton);
        this.initTomSelect(this.userSelect.querySelector('select'), this.options);

        UserList.instanceCount++;
        UserList.instances.push(this);

        if (UserList.instanceCount > 1) {
            UserList.instances[0].ensureDeleteButton();
        }

        if (UserList.instanceCount === 1) {
            this.removeDeleteButton();
        } else {
            this.addDeleteButton();
        }
    }

    static clearInstances() {
        UserList.instances.forEach(instance => instance.listParent.remove());
        UserList.instances = [];
        UserList.instanceCount = 0;
    }

    static getInstanceCount() {
        return UserList.instanceCount;
    }

    static getInstances() {
        return UserList.instances;
    }

    createListParentElement() {
        const parentElement = document.createElement('div');
        parentElement.classList.add('list-group');
        return parentElement;
    }

    createUserSelectElement() {
        const userSelectWrapper = document.createElement('div');
        userSelectWrapper.classList.add('list-group-item');

        const userSelect = document.createElement('select');
        userSelect.classList.add('first-select');
        userSelect.name = 'name_select';

        userSelectWrapper.appendChild(userSelect);
        return userSelectWrapper;
    }

    createRoleSelectElement() {
        const roleSelectWrapper = document.createElement('div');
        roleSelectWrapper.classList.add('list-group-item');

        const roleSelect = document.createElement('select');
        roleSelect.classList.add('second-select');
        roleSelect.name = 'role_select';

        const roles = ['Co-Principal Investigator', 'Science PI', 'Co-Investigator', 'Postdoctoral Associate', 'Collaborator', 'Graduate Student', 'Support'];
        roles.forEach(role => {
            const option = document.createElement('option');
            option.value = role;
            option.textContent = role;
            roleSelect.appendChild(option);
        });

        roleSelectWrapper.appendChild(roleSelect);
        return roleSelectWrapper;
    }

    createAddButton() {
        const buttonWrapper = document.createElement('div');
        buttonWrapper.classList.add('gfield_list_icons', 'gform-grid-col');

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.classList.add('add_list_item', 'custom');
        addButton.ariaLabel = 'Add another row';
        addButton.textContent = 'Add';

        addButton.onclick = (e) => {
            e.preventDefault();
            const newUserList = new UserList(this.options);
            this.listParent.after(newUserList.listParent);

            if (UserList.instanceCount > 1) {
                UserList.instances[0].ensureDeleteButton();
            }
        };

        buttonWrapper.appendChild(addButton);
        return buttonWrapper;
    }

    addDeleteButton() {
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.classList.add('delete_list_item');
        deleteButton.ariaLabel = 'Remove row';
        deleteButton.textContent = 'Remove';

        deleteButton.onclick = (e) => {
            e.preventDefault();
            const index = UserList.instances.indexOf(this);
            if (index !== -1) {
                UserList.instances.splice(index, 1);
            }
            UserList.instanceCount--;
            this.listParent.remove();

            if (UserList.instanceCount === 1) {
                UserList.instances[0].removeDeleteButton();
            }
        };

        const buttonWrapper = this.listParent.querySelector('.gfield_list_icons');
        buttonWrapper.appendChild(deleteButton);
        this.deleteBtn = deleteButton;

    }

    removeDeleteButton() {
        if (this.deleteBtn) {
            this.deleteBtn.remove();
            this.deleteBtn = null;
        }
    }

    ensureDeleteButton() {
        if (!this.deleteBtn) {
            this.addDeleteButton();
        }
    }

    initTomSelect(select, options) {
        this.tsSelect = new TomSelect(select, {
            options: options,
            maxOptions: null,
            hideSelected: true,
        });
    }

    setUser(user) {
        if (this.tsSelect) {
            this.tsSelect.setValue(user);
        } else {
            console.error("TomSelect not initialized");
        }
    }

    setRole(role) {
        this.roleSelect.firstChild.value = role;
       
    }
}

document.getElementById('gform_8').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const other_psi_personnel = document.querySelector('#other-psi_personnel-input');
    const ogTeam = document.querySelector('#other-psi_personnel-ogteam');

    // Get all rows of data
    const rows = document.querySelectorAll('.list-group');

    // Create an array to store row data
    const rowData = Array.from(rows).map(row => {
        // Get input fields within the row
        const userSelect = row.querySelector('.first-select').value;
        const roleSelect = row.querySelector('.second-select').value;

        return {
            name: userSelect,
            role: roleSelect
        };
    });

    // Store row data and original team data in hidden inputs
    other_psi_personnel.value = JSON.stringify(rowData);
    ogTeam.value = JSON.stringify(originalTeam);

    // Submit the form
    this.submit();
});

const featuredImageFUP = document.querySelector('.featured-image-fup');
const imagePreviewer = document.querySelector('.featured-image-previewer');

const imagePreviewerDeleteBtn = imagePreviewer.querySelector('button');
imagePreviewerDeleteBtn.addEventListener('click', function(e) {
    e.preventDefault();
    let val = document.querySelector('.featured-preview-value input');
    val.value = '';

    let inputField = imagePreviewer.querySelector('.fup-text-input');
    inputField.value = '';

    let event = new Event('change', { bubbles: true });
    val.dispatchEvent(event);
});