import Swal from 'sweetalert2';

// Expose to global so existing inline scripts can use `Swal`
window.Swal = Swal;

export default Swal;
