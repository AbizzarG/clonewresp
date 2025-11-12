// Make showOrderDetail and hideOrderDetail globally available
if (typeof window.showOrderDetail === 'undefined') {
    console.warn('showOrderDetail function should be defined in orderHistory.js');
}