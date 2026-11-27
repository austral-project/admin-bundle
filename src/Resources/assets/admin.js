/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Entry of the administration (built by the project, see encore.mjs): the design system, then the admin styles.
import "@austral/design/styles/app.scss";
import "@austral/admin/styles/admin.scss";
import { Loader, DomEventManager } from "@austral/design";

DomEventManager.addListener("DOMContentLoaded", async () => {
  await Loader.initialize();
});
