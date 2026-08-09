import Quill from 'quill';
import 'quill/dist/quill.snow.css';

// Expose Quill so inline Alpine (x-data) can build editors on demand.
window.Quill = Quill;
