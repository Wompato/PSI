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
    
    constructor(options, bool = true, args = null) {
        UserList.instanceCount++;
        UserList.instances.push(this)
        this.listParent = this.createUserListElement(options);
        this.userSelect;
        this.tsSelect;
        this.roleSelect;
        this.instituteInput;
        this.buttonsWrapper;
        this.deleteBtn;
        this.hasDeleteBtn = bool;
        this.initTs(this.userSelect, options);
        if (args && args.user && args.role) {
            this.tsSelect.setValue(args.user);
            this.roleSelect.value = args.role;
        }
        

    }

    static clearInstances() {
        const listRoot = document.querySelector('.other_psi_personnel');
        if(UserList.instances.length > 0) {
            UserList.instances = [];
        }
        UserList.instanceCount = 0;
        const lists = listRoot.querySelectorAll('.list-group');
        lists.forEach(list => {
            list.remove();
        });
    }

    createListParentElement() {
        const parentElement = document.createElement('div');
        parentElement.classList.add('list-group');
        return parentElement;
    }
    
    createUserSelectElement(options) {
        const userSelectWrapper = document.createElement('div');
        userSelectWrapper.classList.add('list-group-item');
        const userSelect = document.createElement('select');
        userSelect.classList.add('first-select');
        userSelect.name = 'name_select';
        //new TomSelect(userSelect, { options: options, maxOptions: null });
        userSelectWrapper.appendChild(userSelect);
        this.userSelect = userSelect;
        return userSelectWrapper;
    }
    
    createRoleSelectElement() {
        const roleSelectWrapper = document.createElement('div');
        roleSelectWrapper.classList.add('list-group-item');
        const roleSelect = document.createElement('select');
        roleSelect.classList.add('second-select');
        roleSelect.name = 'role_select';
        const roleSelectOptions = ['Co-Principal Investigator', 'Science PI', 'Co-Investigator', 'Postdoctoral Associate', 'Collaborator', 'Graduate Student', 'Support'];
        roleSelectOptions.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            roleSelect.appendChild(optionElement);
        });
        this.roleSelect = roleSelect;
        roleSelectWrapper.appendChild(roleSelect);
        return roleSelectWrapper;
    }
    
    /* createInputElement() {
        const inputWrapper = document.createElement('div');
        inputWrapper.classList.add('list-group-item');
        const inputElement = document.createElement('input');
        inputElement.setAttribute('type', 'text');
        inputElement.name = 'institution_input';
        inputWrapper.appendChild(inputElement);
        return inputWrapper;
    } */
    
    createAddListButton(options) {
        const buttonWrapper = document.createElement('div');
        buttonWrapper.classList.add('gfield_list_icons', 'gform-grid-col');
        const addListButton = document.createElement('button');
        addListButton.setAttribute('type', 'button');
        addListButton.classList.add('add_list_item', 'custom');
        addListButton.setAttribute('aria-label', 'Add another row');
        addListButton.textContent = 'Add';
        addListButton.onclick = function(e) {
            e.preventDefault();
            const userList = new UserList(options, true);
           // console.log('I am create Add Buttom function', UserList.instanceCount, UserList.instances[0])
            if(UserList.instanceCount > 1 && UserList.instances[0].hasDeleteBtn === false) {
                
                UserList.instances[0].createUserListDeleteBtn();
                UserList.instances[0].hasDeleteBtn = true;
                
            }
    
            this.parentElement.parentElement.insertAdjacentElement('afterend', userList.listParent);
            
        };
        buttonWrapper.appendChild(addListButton);
        this.buttonsWrapper = buttonWrapper;
        
        
        return buttonWrapper;
    }

    createUserListDeleteBtn() {
        if(UserList.instanceCount <= 1){
            return;
        }
        
        const deleteButton = document.createElement('button');
        deleteButton.setAttribute('type', 'button');
        deleteButton.classList.add('delete_list_item');
        deleteButton.setAttribute('aria-label', 'Remove row {0}');
        deleteButton.setAttribute('data-aria-label-template', 'Remove row {0}');
        deleteButton.textContent = 'Remove';
        deleteButton.onclick = (e) => {
            e.preventDefault();
            const index = UserList.instances.indexOf(this);
            if (index !== -1) {
                UserList.instances.splice(index, 1);
            }
            UserList.instanceCount--;
            
            this.listParent.remove()
            
           
            if(UserList.instanceCount == 1 && UserList.instances[0].hasDeleteBtn == true) {
                
                UserList.instances[0].listParent.children[2].lastChild.remove()
                UserList.instances[0].hasDeleteBtn = false;
                
            }
           
        };
        
        // Append the button to the desired parent element
        // For example, if you want to append it to a div with class "buttons-container"
        
        this.deleteBtn = deleteButton;
        this.buttonsWrapper.appendChild(deleteButton)
       
    }

    // Function to create the user list element
    createUserListElement(options) {
        console.log(UserList.instanceCount);
            if(UserList.instanceCount > 1 && UserList.instances[0].hasDeleteBtn === false) {
                
                UserList.instances[0].createUserListDeleteBtn();
                UserList.instances[0].hasDeleteBtn = true;
                
            }
        const mainDiv = this.createListParentElement();
        mainDiv.appendChild(this.createUserSelectElement(options));
        mainDiv.appendChild(this.createRoleSelectElement());
        
        mainDiv.appendChild(this.createAddListButton(options));
        this.createUserListDeleteBtn();
        return mainDiv;
    }

    initTs(select, options) {
        
        let s = new TomSelect(select, { options: options, maxOptions: null });
        this.tsSelect = s;
    }
}

window.addEventListener('DOMContentLoaded', function() {
    getUsers()
        .then(options => {
            const rootEl = document.querySelector('.other_psi_personnel');
            const uList = new UserList(options, false);
            rootEl.appendChild(uList.listParent);
        })
        .catch(error => {
            console.error('Error fetching users:', error);
        });  
});

// Serialize form data before submission
document.getElementById('gform_10').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission
    const gformSubmitBtn = document.getElementById('gform_submit_button_10');

    let other_psi_personnel = document.querySelector('#other-psi_personnel-input');

   
    // Get all rows of data
    const rows = document.querySelectorAll('.list-group');

    const rowData = [];

    // Iterate over each row
    rows.forEach(row => {
        // Get input fields within the row
        const userSelect = row.querySelector('.first-select').value;
        const roleSelect = row.querySelector('.second-select').value;
        //const inputElement = row.querySelector('input[type="text"]').value;

        // Create an object for the row data
        const rowObject = {
            name: userSelect,
            role: roleSelect,
           // institution: inputElement
        };

        // Push the row object to the rowData array
        rowData.push(rowObject);
    });

 

    other_psi_personnel.value = JSON.stringify(rowData);
   

        this.submit();
        
    });
