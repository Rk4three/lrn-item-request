// js/script.js

window.onload = function () {
  populateGlobalCategories();
    populateGlobalCategories();
    addRow();
};

function populateGlobalCategories() {
  const catList = document.getElementById("categoryOptions");
  if (typeof categoryList !== "undefined" && categoryList) {
    let options = "";
    categoryList.forEach((cat) => {
      options += `<option value="${cat}">`;
    });
    catList.innerHTML = options;
  }
}

// Utility function to escape HTML special characters in strings
function escapeHtml(str) {
  if (!str) return '';
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Utility function to decode HTML entities back to characters
function decodeHtmlEntities(str) {
  if (!str) return '';
  const textarea = document.createElement('textarea');
  textarea.innerHTML = str;
  return textarea.value;
}

function addRow() {
  const container = document.getElementById("itemsContainer");
  if (!container) return; // Guard clause

  const index = container.children.length;

  const card = document.createElement("div");
  card.className =
    "bg-white p-5 rounded-xl shadow-sm border border-gray-200 relative group hover:shadow-md transition-all";
  card.id = `item-row-${index}`;

  // Styles
  const inputBase =
    "w-full border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-primary-light focus:border-primary-dark px-3 py-2 transition-all outline-none";
  const readOnlyBase =
    "w-full bg-gray-50 text-gray-500 border border-gray-200 rounded-lg text-sm font-medium px-3 py-2 cursor-not-allowed select-none";
  const labelBase = "block text-xs font-bold text-gray-500 uppercase mb-1";

  card.innerHTML = `
        <button type="button" onclick="removeRow(this)" 
            class="absolute top-0 right-0 p-3 text-slate-300 hover:text-red-500 rounded-tr-xl rounded-bl-2xl transition-all z-10" 
            title="Remove Item">
            <i class="ph-bold ph-x text-lg"></i>
        </button>

        <div class="grid grid-cols-2 md:grid-cols-12 gap-x-3 gap-y-4">
            
            <div class="col-span-1 md:col-span-2">
                <label class="${labelBase}">Category</label>
                <input list="categoryOptions" 
                       name="items[${index}][category]" 
                       id="cat-${index}"
                       class="${inputBase}" 
                       placeholder="Select..." 
                       onchange="filterItemsByCategory(this, ${index})" 
                       required>
            </div>

            <div class="col-span-1 md:col-span-2">
                <label class="${labelBase}">Item Code</label>
                <input type="text" name="items[${index}][item_code]" id="code-${index}" readonly class="${readOnlyBase}" placeholder="---">
            </div>

            <div class="col-span-2 md:col-span-4">
                <label class="${labelBase}" id="itemNameLabel-${index}">Item Name</label>
                <input list="itemList-${index}" 
                       name="items[${index}][item_name]" 
                       class="${inputBase}" 
                       placeholder="Select category first..." 
                       id="itemName-${index}"
                       onchange="fillDetails(this, ${index})" 
                       required autocomplete="off" disabled>
                <datalist id="itemList-${index}"></datalist>
            </div>

            <div class="col-span-1 md:col-span-2">
                <label class="${labelBase}">Sub Group</label>
                <input type="text" name="items[${index}][sub_group]" id="sub-${index}" class="${inputBase}" 
                       placeholder="e.g. Laundry" oninput="updateApprover(this.value, ${index})">
            </div>

            <div class="col-span-1 md:col-span-2">
                <label class="${labelBase}">UOM</label>
                <input type="text" name="items[${index}][uom]" id="uom-${index}" class="${inputBase}" placeholder="Pc/Set">
            </div>

            <div class="col-span-1 md:col-span-3" id="size-wrapper-${index}">
                <label class="${labelBase}">Size / Unit</label>
                <div class="size-input-container">
                    <input type="text" 
                           name="items[${index}][size]" 
                           id="size-${index}" 
                           class="${inputBase}" 
                           placeholder="Size"
                           autocomplete="off"
                           list="sizeList-${index}">
                     <datalist id="sizeList-${index}"></datalist>
                </div>
            </div>

            <div class="col-span-1 md:col-span-2" id="qty-wrapper-${index}">
                <label class="${labelBase}">QTY</label>
                <input type="number" name="items[${index}][qty]" id="qty-${index}" value="1" min="1" 
                       class="${inputBase} font-bold text-primary-dark text-center"
                       oninput="calculateDeduction()" onblur="validateQty(this)">
            </div>

            <div class="col-span-2 md:col-span-2" id="itemsNeeded-wrapper-${index}" style="display: none;">
                <label class="${labelBase}">Items Needed (with qty)</label>
                <input type="text" 
                       name="items[${index}][items_needed]" 
                       id="itemsNeeded-${index}" 
                       class="${inputBase}" 
                       placeholder="e.g., Mop x2">
            </div>

            <input type="hidden" name="items[${index}][approver]" id="app-${index}" value="">
        </div>
    `;

  card.style.opacity = "0";
  card.style.transform = "translateY(10px)";
  container.appendChild(card);

  // populateApproverDatalist(index); // Removed

  requestAnimationFrame(() => {
    card.style.transition = "all 0.3s ease";
    card.style.opacity = "1";
    card.style.transform = "translateY(0)";
  });
}

function validateQty(input) {
    if (!input.value || parseInt(input.value) < 1) {
        input.value = 1;
        calculateDeduction();
    }
}

// Populate approver department datalist
function populateApproverDatalist(index) {
  const datalist = document.getElementById(`approverList-${index}`);
  if (!datalist) return;
  
  if (typeof approverDepartments !== 'undefined' && approverDepartments) {
    let options = "";
    approverDepartments.forEach(dept => {
      options += `<option value="${dept}">`;
    });
    datalist.innerHTML = options;
  }
}

function showErrorModal(title, message) {
    const modal = document.getElementById('errorModal');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');
    const titleEl = document.getElementById('modalTitle');
    const msgEl = document.getElementById('modalMessage');

    if(title) titleEl.textContent = title;
    if(message) msgEl.textContent = message;

    modal.classList.remove('hidden');
    // Force reflow
    void modal.offsetWidth;

    backdrop.style.opacity = '1';
    panel.style.opacity = '1';
    panel.style.transform = 'translateY(0) scale(1)';
}

function closeErrorModal() {
    const modal = document.getElementById('errorModal');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');

    backdrop.style.opacity = '0';
    panel.style.opacity = '0';
    panel.style.transform = 'translateY(4px) scale(0.95)';

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function validateApprovers() {
  const authSection = document.getElementById('authDeductSection');
  const authCheckbox = document.getElementById('authDeductCheckbox');
  const companyCheckbox = document.getElementById('companyIssuedCheckbox');

  // If section is visible (meaning there is a deduction amount)
  if (authSection && authSection.style.display !== 'none') {
      const isAuthorized = authCheckbox && authCheckbox.checked;
      const isCompanyIssued = companyCheckbox && companyCheckbox.checked;

      if (!isAuthorized && !isCompanyIssued) {
          showErrorModal("Authorization Required", "Please authorize the salary deduction or mark the item(s) as Company Issued to proceed.");
          return false;
      }
  }

  // Validate Sizes
  const itemRows = document.querySelectorAll('div[id^="item-row-"]');
  for (const row of itemRows) {
      const index = row.id.split('-')[2];
      const sizeInput = document.getElementById(`size-${index}`);
      const itemNameInput = document.getElementById(`itemName-${index}`);
      
      if (sizeInput && itemNameInput) {
          const itemName = itemNameInput.value.trim();
          const size = sizeInput.value.trim();
          
          // If item is selected (name not empty) and size input is visible/enabled but empty
          if (itemName && !sizeInput.disabled && !size) {
              showErrorModal("Missing Size", `Please select or enter a size for item: ${itemName}`);
              return false;
          }
      }
  }

  return true;
}

function calculateDeduction() {
  const authSection = document.getElementById('authDeductSection');
  const deductionDisplay = document.getElementById('deductionAmount');
  const companyIssuedCheckbox = document.getElementById('companyIssuedCheckbox');

  if (!authSection) return;

  let totalDeduction = 0;
  const itemRows = document.querySelectorAll('div[id^="item-row-"]');

  itemRows.forEach(row => {
      const index = row.id.split('-')[2];
      const itemNameInput = document.getElementById(`itemName-${index}`);
      const qtyInput = document.getElementById(`qty-${index}`);

      if (itemNameInput && qtyInput) {
          const itemName = itemNameInput.value;
          const qty = parseFloat(qtyInput.value) || 0;

          let price = 0;
          
          // Try to find in ItemMaster first
          const itemData = itemMaster.find(i => i.item_name === itemName);
          if (itemData) {
              price = parseFloat(itemData.price || 0);
          } 
          // If not found (e.g. grouped uniform), check uniformSizeMap
          else if (typeof uniformSizeMap !== 'undefined' && uniformSizeMap[itemName]) {
              price = parseFloat(uniformSizeMap[itemName].price || 0);
          }

          if (price > 0) {
              totalDeduction += (price * qty);
          }
      }
  });

  if (totalDeduction > 0) {
      authSection.style.display = 'flex';
      
      const isWaived = companyIssuedCheckbox && companyIssuedCheckbox.checked;

      if (deductionDisplay) {
          if (isWaived) {
               deductionDisplay.textContent = 'PHP ' + totalDeduction.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (Waived)';
               deductionDisplay.classList.remove('text-pink-500');
               deductionDisplay.classList.add('text-green-500');
          } else {
              deductionDisplay.textContent = 'PHP ' + totalDeduction.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
              deductionDisplay.classList.add('text-pink-500');
              deductionDisplay.classList.remove('text-green-500');
          }
      }
  } else {
      authSection.style.display = 'none';
      const checkbox = document.querySelector('input[name="auth_deduct"]');
      if (checkbox) checkbox.checked = false;
      if (companyIssuedCheckbox) companyIssuedCheckbox.checked = false;
  }
}

function removeRow(btn) {
  const card = btn.closest('div[id^="item-row-"]');
  card.style.opacity = "0";
  card.style.transform = "scale(0.95)";
  setTimeout(() => {
    card.remove();
    // Recalculate deduction
    calculateDeduction();
  }, 200);
}

function filterItemsByCategory(categoryInput, index) {
  const selectedCategory = categoryInput.value;
  const itemInput = document.getElementById(`itemName-${index}`);
  const dataList = document.getElementById(`itemList-${index}`);

  // Clear current items and code
  itemInput.value = "";
  document.getElementById(`code-${index}`).value = "";
  const sizeInput = document.getElementById(`size-${index}`);
  sizeInput.value = "";
  sizeInput.placeholder = "Size";
  sizeInput.disabled = false;
  dataList.innerHTML = "";

  if (selectedCategory) {
    itemInput.disabled = false;
    itemInput.placeholder = "Select Item...";

    const categoryLower = selectedCategory.toLowerCase();
    const isUniformCategory = categoryLower.includes('uniform') || categoryLower.includes('ppe');

    let options = "";
    
    if (isUniformCategory && typeof uniformSizeMap !== 'undefined' && Object.keys(uniformSizeMap).length > 0) {
        // For uniforms, show base names from uniformSizeMap
        const usedNames = new Set();
        
        for (const baseName of Object.keys(uniformSizeMap)) {
            const uniformData = uniformSizeMap[baseName];
            
            // Skip if already added (shouldn't happen but safety check)
            if (usedNames.has(baseName)) continue;
            usedNames.add(baseName);
            
            // Uniform allowance check (if restrictions are defined)
            if (typeof uniformAllowances !== 'undefined' && uniformAllowances.length > 0) {
                const uniformCheck = checkUniformAllowance(baseName);
                if (!uniformCheck.allowed || uniformCheck.remaining <= 0) {
                    continue; // Skip - not allowed or quota exceeded
                }
            }
            
            options += `<option value="${escapeHtml(baseName)}">`;
        }
    } else {
        // For non-uniforms, use regular itemMaster filtering
        const filteredItems = itemMaster.filter(
            (item) => item.category === selectedCategory
        );

        filteredItems.forEach((item) => {
            // Restriction check for roles
            if (item.restricted_roles) {
                const role = (typeof userPosition !== "undefined" ? userPosition : "").toLowerCase();
                const allowedRoles = item.restricted_roles.toLowerCase().split(',').map(r => r.trim());
                const isAllowed = allowedRoles.some(allowed => role.includes(allowed));
                if (!isAllowed) return;
            }

            options += `<option value="${escapeHtml(item.item_name)}">`;
        });
    }

    dataList.innerHTML = options;
  } else {
    itemInput.disabled = true;
    itemInput.placeholder = "Select category first...";
  }
  
  // Recalculate deduction (clearing item clears price)
  calculateDeduction();
  
  // Handle Services category special logic
  handleServicesCategory(index, selectedCategory.toLowerCase() === 'services');
}

/**
 * Check if a uniform item is allowed for the current user and how many they can still request
 * @param {string} itemName - The item name to check
 * @returns {object} - { allowed: bool, maxQuantity: int, remaining: int, issuance: string }
 */
function checkUniformAllowance(itemName) {
    if (typeof uniformAllowances === 'undefined' || !uniformAllowances || uniformAllowances.length === 0) {
        // No restrictions defined - allow all
        return { allowed: true, maxQuantity: 999, remaining: 999, issuance: 'none' };
    }
    
    // Normalize item name for matching - lowercase and keep only letters/numbers/spaces
    const normalizedItemName = itemName.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
    
    // Find matching allowance from user's allowed uniforms
    let matchedAllowance = null;
    
    for (const allowance of uniformAllowances) {
        // Normalize the CSV allowance name the same way
        const normalizedAllowance = allowance.name.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
        
        // Split into keywords (words with 2+ chars)
        const keywords = normalizedAllowance.split(' ').filter(k => k.length >= 2);
        
        if (keywords.length === 0) continue;
        
        // Check if ALL keywords from CSV are found in the item name
        const allKeywordsMatch = keywords.every(keyword => normalizedItemName.includes(keyword));
        
        if (allKeywordsMatch) {
            matchedAllowance = allowance;
            break;
        }
    }
    
    if (!matchedAllowance) {
        // Fallback for items NOT in the CSV (Uniform Allowance List)
        // We must distinguish between PPEs (Generally Allowed) and Restricted Uniforms (Clothes)
        
        const lowerItem = itemName.toLowerCase();

        // 1. Explicit PPE Whitelist - Keywords for items that should be allowed
        const ppeKeywords = [
            'shoe', 'boot', 'glove', 'helmet', 'goggle', 'glass', 
            'ear', 'plug', 'harness', 'safety', 'cover', 'apron',
            'mop', 'broom', 'brush', 'rag', 'soap', 'alcohol', // Cleaning/Hygiene assumed allowable
            'tissue', 'towel' 
        ];

        // Check if item contains any PPE keyword
        const isPPE = ppeKeywords.some(keyword => lowerItem.includes(keyword));

        if (isPPE) {
             // Logic for specific PPEs to set reasonable limits
             let limit = 10;
             let issuance = 'none';

             if (lowerItem.includes('shoe') || lowerItem.includes('boot')) {
                 limit = 1; 
                 issuance = 'deployment';
             } else if (lowerItem.includes('glove')) {
                 limit = 20;
                 issuance = 'daily';
             }

             return { 
                 allowed: true, 
                 reason: 'Standard PPE/Supply', 
                 maxQuantity: limit, 
                 remaining: limit, 
                 issuance: issuance 
             };
        }

        // 2. Default Block - If it's not in the CSV and not a recognized PPE, it's likely a restricted Uniform (Polo, Gown, etc.)
        return { 
            allowed: false, 
            reason: 'Item not listed in your uniform allowance.', 
            maxQuantity: 0, 
            remaining: 0, 
            issuance: 'none' 
        };
    }
    
    // Calculate remaining quota based on history
    const maxQty = matchedAllowance.maxQuantity;
    const issuance = matchedAllowance.issuance;
    
    let alreadyRequested = 0;
    
    if (typeof uniformRequestHistory !== 'undefined' && uniformRequestHistory) {
        const historyType = issuance === 'daily' ? 'daily' : 'deployment';
        const history = uniformRequestHistory[historyType] || {};
        
        // Normalize the allowance name for comparison
        const normalizedAllowance = matchedAllowance.name.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
        const keywords = normalizedAllowance.split(' ').filter(k => k.length >= 2);
        
        // Check for matching items in history
        for (const [histItemName, qty] of Object.entries(history)) {
            const normalizedHistItem = histItemName.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
            
            // If all keywords match, it's the same type of uniform
            const allMatch = keywords.every(keyword => normalizedHistItem.includes(keyword));
            if (allMatch) {
                alreadyRequested += qty;
            }
        }
    }
    
    const remaining = Math.max(0, maxQty - alreadyRequested);
    
    return {
        allowed: true,
        maxQuantity: maxQty,
        remaining: remaining,
        issuance: issuance
    };
}

function fillDetails(inputElement, index) {
  const selectedName = inputElement.value.trim();
  
  // Skip validation if user cleared the field (blank/empty)
  if (!selectedName) {
    document.getElementById(`code-${index}`).value = "";
    document.getElementById(`sub-${index}`).value = "";
    document.getElementById(`uom-${index}`).value = "";
    
    // Reset size input
    const sizeInput = document.getElementById(`size-${index}`);
    if (sizeInput) {
        sizeInput.placeholder = "Size";
        sizeInput.disabled = false;
        sizeInput.value = "";
    }
    // Reset quantity limits
    const qtyInput = document.getElementById(`qty-${index}`);
    if (qtyInput) {
        qtyInput.removeAttribute('max');
        qtyInput.title = '';
    }
    // Remove any allowance info display
    removeAllowanceInfo(index);
    calculateDeduction();
    return;
  }
  
  // Decode HTML entities for proper comparison (handles items with quotes, etc.)
  const decodedName = decodeHtmlEntities(selectedName);
  
  // Check if this is a uniform from uniformSizeMap
  const categoryInput = document.getElementById(`cat-${index}`);
  const categoryValue = categoryInput ? categoryInput.value.toLowerCase() : '';
  const isUniformCategory = categoryValue.includes('uniform') || categoryValue.includes('ppe');
  
  // Debug logging
  console.log('fillDetails called:', { 
      selectedName, 
      decodedName, 
      categoryValue: categoryInput ? categoryInput.value : 'INPUT NOT FOUND',
      isUniformCategory 
  });
  console.log('uniformSizeMap keys:', typeof uniformSizeMap !== 'undefined' ? Object.keys(uniformSizeMap).slice(0, 10) : 'undefined');
  
  // Find matching uniform in uniformSizeMap
  let matchedUniformName = null;
  if (isUniformCategory && typeof uniformSizeMap !== 'undefined') {
      // Try exact match first
      if (uniformSizeMap[selectedName]) {
          matchedUniformName = selectedName;
      } else if (uniformSizeMap[decodedName]) {
          matchedUniformName = decodedName;
      } else {
          // Try flexible matching - find key that matches
          for (const key of Object.keys(uniformSizeMap)) {
              if (key === selectedName || key === decodedName) {
                  matchedUniformName = key;
                  break;
              }
              // Also try case-insensitive match
              if (key.toLowerCase() === selectedName.toLowerCase() || key.toLowerCase() === decodedName.toLowerCase()) {
                  matchedUniformName = key;
                  break;
              }
          }
      }
      console.log('Matched uniform name:', matchedUniformName);
  }
  
  if (isUniformCategory && matchedUniformName && uniformSizeMap[matchedUniformName]) {
      // --- UNIFORM FROM SIZE MAP ---
      const uniformData = uniformSizeMap[matchedUniformName];
      const sizes = uniformData.sizes || {};
      const sizeKeys = Object.keys(sizes);
      
      // Fill sub_group and uom
      document.getElementById(`sub-${index}`).value = uniformData.sub_group || "";
      document.getElementById(`uom-${index}`).value = uniformData.uom || "pc";
      updateApprover(uniformData.sub_group, index);
      
      // Check if this is a one-size item (N/A size)
      if (sizeKeys.length === 1 && sizeKeys[0] === 'N/A') {
          // One-size item detected (could be Shirt, Bonnet, etc.)
          // Fill code for generic item
          document.getElementById(`code-${index}`).value = sizes['N/A'];
          
          // User requested: "size is N/A by default, it should be empty by default"
          // We enable it so they can type a size (e.g. "Large") if needed
          const sizeInput = document.getElementById(`size-${index}`);
          sizeInput.value = ""; 
          sizeInput.placeholder = "Enter Size";
          sizeInput.disabled = false;
      } else {
          // Item has multiple sizes - clear code until size is selected
          document.getElementById(`code-${index}`).value = "";
          const sizeInput = document.getElementById(`size-${index}`);
          sizeInput.disabled = false;
          sizeInput.placeholder = `Available: ${sizeKeys.join(', ')}`;
          sizeInput.value = "";
          
          // Create size datalist
          const sizeDatalistId = `sizeList-${index}`;
          let sizeDatalist = document.getElementById(sizeDatalistId);
          if (!sizeDatalist) {
              sizeDatalist = document.createElement('datalist');
              sizeDatalist.id = sizeDatalistId;
              sizeInput.parentNode.appendChild(sizeDatalist);
              sizeInput.setAttribute('list', sizeDatalistId);
          }
          sizeDatalist.innerHTML = sizeKeys.map(s => `<option value="${s}">`).join('');
          
          // Add size change handler to fill item code - capture matchedUniformName in closure
          const uniformBaseName = matchedUniformName;
          sizeInput.onchange = function() {
              handleUniformSizeChange(index, uniformBaseName);
          };
          sizeInput.oninput = function() {
              handleUniformSizeChange(index, uniformBaseName);
          };
      }
      
      // Uniform allowance check
      const uniformCheck = checkUniformAllowance(matchedUniformName);
      
      console.log('Uniform check for:', matchedUniformName, uniformCheck);
      console.log('Available sizes:', sizeKeys);
      
      const qtyInput = document.getElementById(`qty-${index}`);
      
      if (uniformCheck.allowed && uniformCheck.remaining > 0 && uniformCheck.issuance !== 'none') {
          if (qtyInput) {
              qtyInput.setAttribute('max', uniformCheck.remaining);
              qtyInput.value = Math.min(parseInt(qtyInput.value) || 1, uniformCheck.remaining);
              
              qtyInput.oninput = function() {
                  const max = parseInt(this.getAttribute('max'));
                  if (max && parseInt(this.value) > max) {
                      this.value = max;
                      showErrorModal(
                          "Quantity Limit Reached",
                          `You can only request up to ${max} of this item (${uniformCheck.issuance === 'daily' ? 'per day' : 'per 6 months'}).`
                      );
                  }
                  calculateDeduction();
              };
          }
          showAllowanceInfo(index, uniformCheck);
      } else {
          if (qtyInput) {
              qtyInput.removeAttribute('max');
              qtyInput.oninput = function() { calculateDeduction(); };
          }
          removeAllowanceInfo(index);
      }
      
      calculateDeduction();
      return;
  }
  
  // --- NON-UNIFORM: Find item in itemMaster ---
  
  // Reset size placeholder and state for non-uniform items
  const sizeInput = document.getElementById(`size-${index}`);
  if (sizeInput) {
      sizeInput.placeholder = "Size";
      sizeInput.disabled = false;
  }

  let itemData = itemMaster.find((i) => i.item_name === selectedName);
  if (!itemData) {
    itemData = itemMaster.find((i) => i.item_name === decodedName);
  }

  if (itemData) {
    // --- EXISTING ITEM ---
    document.getElementById(`code-${index}`).value = itemData.item_code || "";
    document.getElementById(`sub-${index}`).value = itemData.sub_group || "";
    document.getElementById(`uom-${index}`).value = itemData.default_uom || "";
    updateApprover(itemData.sub_group, index);
    
    // Suggest sizes based on history and handle special UI
    handleItemType(itemData, index);
    
    // Reset quantity limits for non-uniforms
    const qtyInput = document.getElementById(`qty-${index}`);
    if (qtyInput) {
        qtyInput.removeAttribute('max');
        qtyInput.oninput = function() { calculateDeduction(); };
    }
    removeAllowanceInfo(index);
    
    // Recalculate deduction
    calculateDeduction();
    
  } else {
    // --- ITEM NOT FOUND ---
    // Prevent adding new items manually
    inputElement.value = ""; // Clear the input
    document.getElementById(`code-${index}`).value = ""; // Clear code
    
    // Show restriction modal
    showErrorModal(
        "Item Not Found", 
        "You cannot add new items directly. Please contact the administrator to add this item to the database."
    );
    return;
  }
}

/**
 * Handle size change for uniform items - fills item code when valid size is selected
 */
function handleUniformSizeChange(index, baseName) {
    const sizeInput = document.getElementById(`size-${index}`);
    const codeInput = document.getElementById(`code-${index}`);
    const selectedSize = sizeInput.value.toUpperCase().trim();
    
    if (!uniformSizeMap[baseName]) return;
    
    const sizes = uniformSizeMap[baseName].sizes || {};
    
    if (sizes[selectedSize]) {
        // Valid size - set item code
        codeInput.value = sizes[selectedSize];
        sizeInput.classList.remove('border-red-300');
        sizeInput.classList.add('border-green-300');
    } else if (selectedSize) {
        // Invalid size entered
        codeInput.value = "";
        sizeInput.classList.remove('border-green-300');
        sizeInput.classList.add('border-red-300');
        
        // Show available sizes
        const availableSizes = Object.keys(sizes).join(', ');
        sizeInput.title = `Available sizes: ${availableSizes}`;
    } else {
        // Empty - reset
        codeInput.value = "";
        sizeInput.classList.remove('border-red-300', 'border-green-300');
    }
}

// Standard Size Definitions
const standardSizes = {
    clothes: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'],
    shoes: ['37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49']
};

function handleItemType(item, index) {
    const category = (item.category || '').toLowerCase();
    const itemName = (item.item_name || '').toLowerCase();
    const sizeInput = document.getElementById(`size-${index}`);

    // Check for Liquids
    if (category.includes('cleaning chemical') || category.includes('liquid') || category.includes('ink')) {
        renderLiquidUI(index);
        return;
    } 
    
    // Revert to standard if needed
    resetStandardUI(index);
    
    // --- Classification Logic ---
    // 1. Identify Shoes first (Takes Priority)
    let isShoes = category.includes('shoes') || itemName.includes('shoes') || itemName.includes('boots') || itemName.includes('safety shoes');

    // 2. Identify Clothes (Only if not shoes)
    let isClothes = false;
    if (!isShoes) {
        isClothes = category.includes('uniform') || category.includes('clothes') || category.includes('jacket');
    }
    
    // Specific Overrides based on User Request
    // "all items from PPE should be N/A except for the boots"
    if (category.includes('ppe')) {
        if (itemName.includes('boots') || itemName.includes('shoes')) {
            isShoes = true;
            isClothes = false; // Force ensure
        } else {
            // Force non-clothes/non-shoes for generic PPE (Helmet, Goggles, etc) => N/A
            isClothes = false;
            isShoes = false;
        }
    }

    // "For tumbler, cap, and facemask must be N/A"
    if (itemName.includes('tumbler') || itemName.includes('cap') || itemName.includes('facemask') || itemName.includes('face mask')) {
        isClothes = false;
        isShoes = false;
    }

    // Populate Datalist
    updateSizeOptions(item.item_name, index, isClothes, isShoes);

    // --- Autofill Logic ---
    if(sizeInput) sizeInput.value = ""; // Reset

    if (isClothes || isShoes) {
        // STRICTLY NO AUTOFILL for Size selection.
        return;
    } 

    // N/A Autofill Candidates
    let shouldAutofillNA = false;

    // 1. Explicit Targets
    if (itemName === 'n/a' || itemName.includes('tumbler') || itemName.includes('cap') || itemName.includes('facemask') || itemName.includes('face mask')) {
        shouldAutofillNA = true;
    }

    // 2. Category Based 
    // "all items from PPE should be N/A except for the boots" (Boots handled above by setting isShoes=true)
    if (category.includes('ppe')) shouldAutofillNA = true;
    if (category.includes('services') || category.includes('labor')) shouldAutofillNA = true;

    // 3. History Based (Weak signal, only if not overridden above)
    if (!shouldAutofillNA && typeof itemSizes !== 'undefined' && itemSizes[item.item_name]) {
        const history = itemSizes[item.item_name];
        if(history.includes('N/A') || history.includes('n/a')) {
            shouldAutofillNA = true; 
        }
    }

    if (shouldAutofillNA) {
        if(sizeInput) sizeInput.value = "N/A";
    }
}

function updateSizeOptions(itemName, index, isClothes, isShoes) {
    const dataList = document.getElementById(`sizeList-${index}`);
    if(!dataList) return;
    
    dataList.innerHTML = '';
    
    // Use an ORDERED list for the final options
    let options = [];

    // 1. Add Defaults (Base Source of Truth)
    if (isClothes) {
        options = [...standardSizes.clothes];
    } else if (isShoes) {
        options = [...standardSizes.shoes];
    }

    // 2. Add History (Merge unique values) 
    // Removed as per user request to shorten dropdown list
    /*
    if (typeof itemSizes !== 'undefined' && itemSizes[itemName]) {
        itemSizes[itemName].forEach(s => {
            // Avoid duplicates and avoid adding 'N/A' into a size list
            if (!options.includes(s) && s !== 'N/A' && s !== 'n/a') {
                 options.push(s);
            }
        });
    }
    */

    // Render Logic
    let html = '';
    options.forEach(size => {
        html += `<option value="${size}">`;
    });
    dataList.innerHTML = html;
}

function renderLiquidUI(index) {
    const wrapper = document.getElementById(`size-wrapper-${index}`);
    if(!wrapper) return;

    // Check if already rendered to avoid re-rendering
    if(wrapper.querySelector('.liquid-ui-container')) return;

    const inputBase = "w-full border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-primary-light focus:border-primary-dark px-3 py-2 transition-all outline-none";

    wrapper.innerHTML = `
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Size / Unit (Volume)</label>
        <div class="flex gap-2 liquid-ui-container">
             <!-- Hidden input to store the final value sent to server -->
             <input type="hidden" name="items[${index}][size]" id="size-${index}" value="1 L">
             
             <!-- Visible Inputs -->
             <div class="relative w-1/2">
                <input type="number" id="liq-qty-${index}" value="1" min="0.1" step="0.1"
                    class="${inputBase}" oninput="updateLiquidValue(${index})">
                <span class="absolute right-3 top-2 text-xs text-gray-400 font-bold">QTY</span>
             </div>
             
             <div class="w-1/2">
                <select id="liq-unit-${index}" class="${inputBase} bg-white" onchange="updateLiquidValue(${index})">
                    <option value="L" selected>L (Liters)</option>
                    <option value="mL">mL (Milliliters)</option>
                    <option value="gal">gal (Gallons)</option>
                </select>
             </div>
        </div>
    `;
}

function updateLiquidValue(index) {
    const qty = document.getElementById(`liq-qty-${index}`).value;
    const unit = document.getElementById(`liq-unit-${index}`).value;
    const hiddenInput = document.getElementById(`size-${index}`);
    if(hiddenInput) {
        hiddenInput.value = `${qty} ${unit}`;
    }
}

function resetStandardUI(index) {
    const wrapper = document.getElementById(`size-wrapper-${index}`);
    if(!wrapper) return;
    
    // Check if we are already in standard mode (check for liquid container)
    if(!wrapper.querySelector('.liquid-ui-container')) return;

    const inputBase = "w-full border border-gray-200 rounded-lg text-sm font-medium focus:ring-2 focus:ring-primary-light focus:border-primary-dark px-3 py-2 transition-all outline-none";
    
    wrapper.innerHTML = `
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Size / Unit</label>
        <div class="size-input-container">
            <input type="text" 
                   name="items[${index}][size]" 
                   id="size-${index}" 
                   class="${inputBase}" 
                   placeholder="Size or Unit"
                   autocomplete="off"
                   list="sizeList-${index}">
             <datalist id="sizeList-${index}"></datalist>
        </div>
    `;
}

function generateNextCode(category) {
  // 1. Define Prefixes based on Category
  const prefixes = {
    "Cleaning Chemical": "CHEM",
    Uniform: "UNI",
    PPE: "PPE",
    Consumables: "CON",
    Services: "SRV",
  };

  const prefix = prefixes[category] || "NEW";

  // 2. Filter items with this prefix and extract numbers
  let maxNum = 0;

  itemMaster.forEach((item) => {
    if (item.item_code && item.item_code.startsWith(prefix)) {
      // Extract the number part (e.g., "015" from "UNI015")
      const numPart = parseInt(item.item_code.replace(prefix, ""), 10);
      if (!isNaN(numPart) && numPart > maxNum) {
        maxNum = numPart;
      }
    }
  });

  // 3. Increment and Format (Pad with zeros to 3 digits)
  const nextNum = maxNum + 1;
  const paddedNum = nextNum.toString().padStart(3, "0");

  return `${prefix}${paddedNum}`;
}

function updateApprover(subGroup, index) {
  // Logic removed: Approver is now automatically the Requestor's Department (handled in backend)
  return;
}

// Handle Services Category Special Logic
function handleServicesCategory(index, isServices) {
  const qtyWrapper = document.getElementById(`qty-wrapper-${index}`);
  const itemsNeededWrapper = document.getElementById(`itemsNeeded-wrapper-${index}`);
  const itemNameLabel = document.getElementById(`itemNameLabel-${index}`);
  const uomField = document.getElementById(`uom-${index}`);
  const sizeField = document.getElementById(`size-${index}`);
  
  if (isServices) {
    // Hide QTY field for Services and force value to 1
    if (qtyWrapper) {
        qtyWrapper.style.display = 'none';
        const qtyInput = document.getElementById(`qty-${index}`);
        if(qtyInput) qtyInput.value = 1;
    }
    
    // Show Items Needed field
    if (itemsNeededWrapper) itemsNeededWrapper.style.display = 'block';
    
    // Update Item Name label
    if (itemNameLabel) itemNameLabel.textContent = 'Service Type';
    
    // Auto-fill N/A for UOM and Size
    if (uomField) uomField.value = 'N/A';
    if (sizeField) sizeField.value = 'N/A';
    
  } else {
    // Show QTY field for non-Services
    if (qtyWrapper) qtyWrapper.style.display = 'block';
    
    // Hide Items Needed field
    if (itemsNeededWrapper) itemsNeededWrapper.style.display = 'none';
    
    // Reset Item Name label
    if (itemNameLabel) itemNameLabel.textContent = 'Item Name';
    
    // Clear Items Needed field
    const itemsNeededField = document.getElementById(`itemsNeeded-${index}`);
    if (itemsNeededField) itemsNeededField.value = '';
  }
}

/**
 * Show uniform allowance info badge next to quantity field
 * @param {number} index - Row index
 * @param {object} uniformCheck - Allowance check result
 */
function showAllowanceInfo(index, uniformCheck) {
    // Remove existing info first
    removeAllowanceInfo(index);
    
    const qtyWrapper = document.getElementById(`qty-wrapper-${index}`);
    if (!qtyWrapper) return;
    
    const issuanceText = uniformCheck.issuance === 'daily' ? 'today' : 'per 6 months';
    const maxText = uniformCheck.maxQuantity === uniformCheck.remaining 
        ? `You can request up to ${uniformCheck.maxQuantity}` 
        : `${uniformCheck.remaining} remaining of ${uniformCheck.maxQuantity} allowed`;
    
    let badgeColor, icon;
    if (uniformCheck.remaining <= 1) {
        badgeColor = 'bg-amber-100 text-amber-700 border border-amber-200';
        icon = 'ph-warning';
    } else {
        badgeColor = 'bg-blue-50 text-blue-700 border border-blue-200';
        icon = 'ph-info';
    }
    
    const infoDiv = document.createElement('div');
    infoDiv.id = `allowance-info-${index}`;
    infoDiv.className = `mt-2 text-xs font-semibold ${badgeColor} px-3 py-2 rounded-lg`;
    infoDiv.innerHTML = `<i class="ph-fill ${icon} mr-1"></i> ${maxText} (${issuanceText})`;
    
    qtyWrapper.appendChild(infoDiv);
}

/**
 * Remove uniform allowance info badge
 * @param {number} index - Row index
 */
function removeAllowanceInfo(index) {
    const existingInfo = document.getElementById(`allowance-info-${index}`);
    if (existingInfo) {
        existingInfo.remove();
    }
}
