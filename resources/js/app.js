import "./bootstrap";

// Ensure audio asset is emitted into Vite manifest for Vite::asset()
import "../music/message1.mp3";
import "../music/message2.mp3";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();
