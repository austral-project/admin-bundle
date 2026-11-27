/*
 * This file is part of the Austral Admin Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import path from "node:path";
import { fileURLToPath } from "node:url";
// design-bundle is installed next to admin-bundle (vendor/austral/design-bundle): a relative import, resolved from
// the real path of this file, also works when the bundles are symlinked (Composer "path" repository).
import configureAustralDesign from "../design-bundle/encore.mjs";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const ASSETS = path.resolve(__dirname, "src/Resources/assets");

/**
 * Configures an Encore instance of the project to build the administration: the design system of design-bundle,
 * then the styles and the scripts of the admin, in one entry.
 *
 * The project calls it from its own webpack.config.mjs, before its own settings (output path, other entries...):
 *
 *   import Encore from "@symfony/webpack-encore";
 *   import configureAustralAdmin from "./vendor/austral/admin-bundle/encore.mjs";
 *
 *   configureAustralAdmin(Encore)
 *     .setOutputPath("public/build/")
 *     .setPublicPath("/build");
 *
 * @param {object} Encore  The Encore instance (import Encore from "@symfony/webpack-encore").
 * @param {object} options Options of configureAustralDesign() (debug, debugContext, themePath), and:
 * @param {string}  [options.entry]  Name of the entry of the administration (default: "austral-admin").
 * @return {object} The Encore instance.
 */
export default function configureAustralAdmin(Encore, options = {})
{
  return configureAustralDesign(Encore, options)
    .addAliases({
      "@austral/admin": ASSETS
    })
    .addEntry(options.entry ?? "austral-admin", path.join(ASSETS, "admin.js"));
}

export { ASSETS as assetsPath };
