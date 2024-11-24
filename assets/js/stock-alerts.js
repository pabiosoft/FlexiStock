import Swal from 'sweetalert2';

export const checkLowStock = (equipment) => {
    const availableStock = equipment.stockQuantity - equipment.reservedQuantity;
    return availableStock <= equipment.minThreshold;
};

export const showLowStockAlert = (equipment) => {
    Swal.fire({
        title: 'Low Stock Alert!',
        text: `${equipment.name} is running low on stock. Current available: ${equipment.stockQuantity - equipment.reservedQuantity}`,
        icon: 'warning',
        confirmButtonText: 'Create Order',
        showCancelButton: true,
        cancelButtonText: 'Dismiss'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/order/create?equipment=${equipment.id}`;
        }
    });
};

export const showExpiryAlert = (equipment) => {
    const today = new Date();
    const warrantyDate = new Date(equipment.warrantyDate);
    const daysUntilExpiry = Math.ceil((warrantyDate - today) / (1000 * 60 * 60 * 24));

    if (daysUntilExpiry <= 30 && daysUntilExpiry > 0) {
        Swal.fire({
            title: 'Warranty Expiring Soon!',
            text: `Warranty for ${equipment.name} will expire in ${daysUntilExpiry} days`,
            icon: 'info',
            confirmButtonText: 'OK'
        });
    }
};

export const setupStockMonitoring = () => {
    const checkStockLevels = async () => {
        try {
            const response = await fetch('/api/equipment/stock-status');
            const equipments = await response.json();
            
            equipments.forEach(equipment => {
                if (checkLowStock(equipment)) {
                    showLowStockAlert(equipment);
                }
                showExpiryAlert(equipment);
            });
        } catch (error) {
            console.error('Error checking stock levels:', error);
        }
    };

    // Check stock levels every hour
    setInterval(checkStockLevels, 3600000);
    
    // Initial check
    checkStockLevels();
};