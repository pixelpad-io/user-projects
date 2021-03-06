stdClass Object
(
    [assets] => stdClass Object
        (
            [code] => stdClass Object
                (
                    [main] => /*
Reminders for PixelPAD Union

- Instance variables need to be initialized! Even basic vars such as integers/strings
- There are no structs. Basic objects such as Vectors pass by reference by default
- Specific Vectors like position, rotation can be set by simply setting their individual variables.
    e.g. transform.position.x = 4 works
- Instantiate is used to create GameObjects with a specific Component or as a prefab

*/


/*




*/
                    [Time] => class Time
{
    static timeStarted :number = 0;

    private static date : Date;

    static Init ()
    {
        this.date = new Date();
        this.timeStarted = this.date.getTime();
    }

    static get deltaTime ()
    {
        return Engine.current.getDeltaTime() * 0.001;                
    }

    static get time ()
    {
        return (new Date().getTime() - this.timeStarted) * 0.001;
    }
}
                    [Debug] => class Debug
{
  static Log (info : any, includeStackTrace : boolean = false)
  {
    var msg = (Time.time.toFixed(3).toString() + ": " + info);
    if (includeStackTrace)
    {
      consolelog(new Error(msg).stack);
    } else {
      consolelog(msg);
    }
  }
}
                    [Engine] => var ENABLE_EDITOR : boolean = false;

class Engine {   

    public static current: BABYLON.Engine;
    public static scene: BABYLON.Scene;
    public static editorScene : BABYLON.Scene;
    public static isPlaying : boolean = false;

    //Can be adjusted for testing, specifically useful for testing memory leaks
    public static get nFramesPerRenderLoop () : number 
    {
        return this._nFramesPerRenderLoop;                
    }

    public static set nFramesPerRenderLoop (value : number)
    {
        Debug.Log("WARNING: nFramesPerRenderLoop is set to " + value + ". This should only happen during testing!");
        this._nFramesPerRenderLoop = value;
    }

    private static _nFramesPerRenderLoop : number = 1;

    public static canvas: HTMLCanvasElement;

    public static toBeDestroyed: SceneObject[];    

    public static meshToObject : Map<number, GameObject>;

    public static editorTypes : string[] = new Array();

    public static cursor = "default";

    public static onUpdate : BABYLON.Observable<number>;

    public static scripts : string[] = new Array();

    public static IsEditorType (value : Component) : boolean
    {
        var isEditorType : boolean = this.editorTypes.indexOf(value.constructor.name) > -1;
        return isEditorType;
    }

    public static isSwitchingToPlayMode : boolean;

    public static SwitchToPlayMode ()
    {
        this.isSwitchingToPlayMode = true;
    }

    public static StopPlayMode ()
    {
        Engine.isPlaying = false;
    }

    public static getUrl(): string {
        if (Engine.url == "") {
            //TODO How to get the URL in the future?
            var modelUrl = getModel("ball");
            var splits = modelUrl.split('/');
            splits.pop();
            Engine.url = splits.join('/') + "/";
        }
        return Engine.url;
    }

    static url: string = "";

    public static getFullName(name: string, fileType: FileType) : string {
        var fullUrl: string = "";
        switch (fileType) {
            case FileType.Model:
                fullUrl = getModel(name);
                break;
            case FileType.Texture:
                fullUrl = getTexture(name);
                break;
            case FileType.Sound:
                fullUrl = getSound(name);
                break;
            default:
                //statements; 
                break;
        }
        if (fullUrl == null || fullUrl == undefined)
        {
            return null;
        }

        return fullUrl.split('/').pop();
    }

    public static ShouldRun (obj : SceneObject) : boolean
    {
        return Engine.isPlaying || Engine.IsEditorType(obj)
    }

    public static OnPlayButton (evt : CustomEvent)
    {
        var buttonText : string = evt.detail.buttonText;
        //When the recompile button is clicked, we first remove all the old listeners
        PixelPADEvents.ClearAllListeners();
        document.removeEventListener("playClicked", Engine.OnPlayButton);
    }

}

enum FileType {
    Model = 1,
    Texture,
    Sound
}

class Playground {

    public static CreateScene(engine: BABYLON.Engine, canvas: HTMLCanvasElement): BABYLON.Scene {

        PixelPADEvents.AddAllListeners();
        document.addEventListener("playClicked", Engine.OnPlayButton);

        Engine.current = engine;
        Engine.canvas = canvas;
        // This creates a basic Babylon Scene object (non-mesh)
        Engine.scene = new BABYLON.Scene(engine);

        Engine.editorScene = new BABYLON.Scene(engine);
        Engine.editorScene.autoClear = false;

        Engine.scene.clearColor = BABYLON.Color4.FromColor3(new BABYLON.Color3(0, 0, 0));

        //Physics are handled in the Physics class
        Engine.scene.enablePhysics(BABYLON.Vector3.Zero());

        Engine.meshToObject = new Map();
        Engine.toBeDestroyed = new Array();

        Engine.onUpdate = new BABYLON.Observable();

        Engine.scripts = Engine.scripts.concat(getScripts());
        
        Time.Init();
        Cursor.Init();
        Material.Init();
        MeshLoader.Init();
        TextureSystem.Init();
        Physics.Init();
        Input.Init();
        SceneManager.Init();
        //Create Shadows and Main Directional Light 
        Shadows.Init();

        if (ENABLE_EDITOR)
        {
            UnionEditor.Init();
        }
        else 
        {
            Engine.isPlaying = true;
        }
        SceneManager.LoadScene("MainScene.scn");

        setTimeout(() => {
            Engine.current.stopRenderLoop();

                    //Used to run multiple scenes

            Engine.current.runRenderLoop(() => {
                Engine.scene.render();
                if (ENABLE_EDITOR)
                {
                    Engine.editorScene.render();
                }
            });
        });

        // Game/Render loop
        Engine.scene.onBeforeRenderObservable.add(() => {
            for (var j = 0; j < Engine.nFramesPerRenderLoop; j++) 
            {
                if (ENABLE_EDITOR)
                {
                    UnionEditor.Update();
                }

                if (Engine.isSwitchingToPlayMode)
                {
                    Engine.isSwitchingToPlayMode = false;
                    Engine.isPlaying = true;
                    return;
                }

                var scene = SceneManager.GetActiveScene();

                if (scene)
                {
                    //Start is not called directly after Awake
                    //This should take care of any objects being created in the Start
                    for (var i = 0; i < scene.newObjs.length; i++) {
                        scene.newObjs[i].InternalStart();
                    }
                    scene.newObjs = new Array();

                    for (var i =scene.objs.length -1; i >= 0 ; i--) {
                        scene.objs[i].InternalUpdate();
                    }

                    Engine.onUpdate.notifyObservers(0);
                }

                Material.Update();
                
                //Updates all 'just' pushed or released keys
                Input.Update();

                //Process detroyed objects
                for (var i = 0; i < Engine.toBeDestroyed.length; i++) {
                    var obj = Engine.toBeDestroyed[i];
                    obj.InternalDestroy();
                }

                Engine.toBeDestroyed = new Array();
                
                SceneManager.Update();


            }
        });        
        
        Engine.scene.onPointerObservable.add((ev) => {
            
            canvas.style.cursor = Engine.cursor;  
        });

        return Engine.scene;
    }

}

//Some helper methods - just like Unity

function Instantiate<T extends MonoBehaviour>(objType: string | (new () => T), startPosition: Vector3 = Vector3.Zero()): T {
   
    return GameObject.Instantiate<T>(objType, startPosition);
}



function Destroy(obj) {
    GameObject.Destroy(obj);
}

function print(info: any) {
    Debug.Log(info);
}

//Attribute
function ExecuteInEditMode(target: Function) {
    Engine.editorTypes.push(target.prototype.constructor.name);
}

                    [PixelPADEvents] => /*
All External events coming from the PixelPAD website go through this wrapper
The only exception is the stop button - since this is handled directly in the engine.
*/

//TODO Clean this up into objects of a PixePADEventListenerClass
class PixelPADEvents {

  public static onSceneClicked : BABYLON.Observable<string>;
  public static onSaveClicked : BABYLON.Observable<string>;
  public static onMaterialClicked : BABYLON.Observable<string>;
  public static onPrefabClicked : BABYLON.Observable<string>;
  
  //Start is called before the first frame update
  static AddAllListeners() 
  {
    PixelPADEvents.onSceneClicked = new BABYLON.Observable();
    PixelPADEvents.onSaveClicked = new BABYLON.Observable();
    PixelPADEvents.onMaterialClicked = new BABYLON.Observable();
    PixelPADEvents.onPrefabClicked = new BABYLON.Observable();

    document.addEventListener("sceneClicked", this.OnSceneClicked);
    document.addEventListener("saveClicked", this.OnSaveClicked);
    document.addEventListener("materialClicked", this.OnMaterialClicked);
    document.addEventListener("prefabClicked", this.OnPrefabClicked);
  }
  
  static ClearAllListeners ()
  {
    document.removeEventListener("sceneClicked", this.OnSceneClicked);
    document.removeEventListener("saveClicked", this.OnSaveClicked);
    document.removeEventListener("materialClicked", this.OnMaterialClicked);
    document.removeEventListener("prefabClicked", this.OnPrefabClicked);

    PixelPADEvents.onSceneClicked.clear();
    PixelPADEvents.onSaveClicked.clear();
    PixelPADEvents.onMaterialClicked.clear();
    PixelPADEvents.onPrefabClicked.clear();
  }

  private static OnSceneClicked (e : CustomEvent)
  {
    PixelPADEvents.onSceneClicked.notifyObservers( e.detail.sceneName);
  }

  private static OnSaveClicked (e : CustomEvent)
  {
    PixelPADEvents.onSaveClicked.notifyObservers(e.detail.buttonText);
  }

  private static OnMaterialClicked (e : CustomEvent)
  {
    PixelPADEvents.onMaterialClicked.notifyObservers(e.detail.materialName);    
  }

  private static OnPrefabClicked (e : CustomEvent)
  {
    PixelPADEvents.onPrefabClicked.notifyObservers(e.detail.prefabName);    
  }
}


                    [Serializer] => class Serializer {
  public static isCreatingGameObject;
  public static isSettingParent;

  private static serializedProperties: Map<any, string[]> = new Map();
  //Some properties are serialized but should not show in inspector
  private static hiddenProperties: Map<any, string[]> = new Map();

  private static isSerializingPrefab;

  static RegisterSerialized(target: any, property: any): void {
    let keys: string[] = this.serializedProperties.get(target);
    if (!keys) {
      keys = [];
      this.serializedProperties.set(target, keys);
    }
    keys.push(property);
  }

  static RegisterHideInInspector(target: any, property: any): void {
    let keys: string[] = this.hiddenProperties.get(target);
    if (!keys) {
      keys = [];
      this.hiddenProperties.set(target, keys);
    }
    keys.push(property);
  }

  //Returns all serialized properties of an object, including it's base classes
  static GetProperties(objType: any, includeHidden: boolean): string[] {
    let keys: string[] = new Array();

    let baseClass: any = Object.getPrototypeOf(objType);

    if (baseClass) {
      //Recursion to append to keys.
      keys = keys.concat(this.GetProperties(baseClass, includeHidden));
    }

    //If it's serialized, but not hidden...
    if (this.serializedProperties.has(objType)) {
      //The props of only this class (not using inheritance)
      let props = this.serializedProperties.get(objType);
      //Copy over the array (is this the best approach?)
      props.forEach((value: string) => {
        //if includeHidden (means ignore HideInInspector refs) OR if it's not a hidden property
        if (includeHidden || !(this.hiddenProperties.has(objType) && this.hiddenProperties.get(objType).indexOf(value) != -1)) {
          keys.push(value)
        }
      }
      );
    }


    return keys;
  }

  static GetPropValues(target: any, includeHidden: boolean): Map<string, any> {
    //The map that we return
    var propValues: Map<string, any> = new Map();

    var targetObjectType = Object.getPrototypeOf(target);

    let keys: string[] = this.GetProperties(targetObjectType, includeHidden);
    //Fill up the map with those strings
    for (const property of keys) {
      propValues.set(property, target[property]);
    }

    return propValues;
  }

  static nl: NextLine;

  static FromJSON(jsonData: string, reportErrors : boolean = true): any {
    try {
      //A cast is only a hint for static code analysis but doesn't have any effect at runtime.
      var jsonObject: any = JSON.parse(jsonData);

      var realObject: any = this.CreateFromJSObj(jsonObject);

      return realObject;
    } catch (e) {
      if (reportErrors)
      {
        Debug.Log("Error creating JSON Object: " + e);
      }
      return null;
    }

    return realObject;
  }

  //owner can be either a Transform (for children GameObjects) or a GameObject (for components)
  static CreateFromJSObj(jsObj: any, propDesc: PropertyDescriptor = null, owner: BaseObject = null): any {
    if (jsObj == null) {
      return null;
    }
    var objType: string = jsObj.t;

    var isCreatingPrefab = false;

    if (objType == "GameObject") {
      if (jsObj.prefabName)
      {
        isCreatingPrefab = true;
      }
      //Used in the GameObject constructor to not create default components when Serializing.
      this.isCreatingGameObject = true;
    }

    //instance creation here. Instance might not exist so we just return null in that case
    try {
    var obj: any = eval("new " + objType + "();");
    } catch (e) {Debug.Log(e);
    return null;}
    this.isCreatingGameObject = false;

    var props = this.GetProperties(obj, true);

    var children: Transform[] = new Array;

    props.forEach((propName: string) => {

      var value = null;
      try {
        var p: PropertyDescriptor = Object.getOwnPropertyDescriptor(jsObj, propName);
        value = p.value;
      } catch (error) {
        //No need to print. This means that the serialized field in the object, could not be found in the JSON
        //This can simply mean that the JSON did not yet contain that serialized property (e.g. if it was just added)
        return;
      }
      if (value === null) {
        //Do nothing (variable can remain null)
      } else if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
        Object.defineProperty(obj, propName, p);

      } else if (value instanceof Array) {
        var isc: boolean = obj instanceof GameObject && propName == "components";

        for (var i: number = 0; i < value.length; i++) {
          value[i] = (this.CreateFromJSObj(value[i], null, isc ? obj : null));
        }

        //We store the 'children' array for later.
        if (obj instanceof Transform && propName == "ser_children") {
          //Removes all null elements from the list.
          //Due to the serialization process more and more null elements get added
          children = value;    
        }

        //If isComponent, we don't need to redefine the 'components' array since we already push the 
        //components through their Awake call.
        if (!isc) {
          Object.defineProperty(obj, propName, p);
        }
      } else {
        //When we use defineProperty, it is ESSENTIAL we set a PropertyDescriptor
        //If we only set the value, it will override access modifiers
        //E.g. after setting, a read+write variable might just become read only 0_o
        Object.defineProperty(obj, propName, this.CreateFromJSObj(value, p));
      }
    });

    //Components need to have Awake called when they're initiated         
    if (owner) {
      obj.InternalPreAwake(owner);
      //Transform component needs it's awakening called beforehand.
      if (objType == "Transform")
      {
        obj.InternalAwake(owner);
        //AFTER the Transform has 'awoken' we can set the children to it.
        //The order here is:
        //1) Start Transform Creation
        //2) Create Children
        //3) Finish Transform Creation
        //4) Set Children to be parented to Transform
        for (var i: number = 0; i < children.length; i++) {
          this.isSettingParent = true;
          if (children[i]?.transform) {
            children[i].transform.SetParent(obj, false);
          }
          this.isSettingParent = false;
        }
      }
      
    }

    if (isCreatingPrefab)
    {
      Prefab.Create(jsObj.prefabName, obj);
    }

    if (objType == "GameObject") {
      var go = obj as GameObject;
      go.components.forEach(c => {
        //Skip the transform component (already handled above)
        if (c.transform != c)
        {
          c.InternalAwake(go);
        }
      });
    }

    if (propDesc) {
      propDesc.value = obj;
      return propDesc;
    }

    return obj;
  }

  static ToJSON(target: any, prettyPrint: boolean = false): string {

    this.nl = new NextLine(prettyPrint);
    return this.GetPropertyJSON(target);
  }

  private static GetPropertyJSON(target: any): string {
    
    var data: string = "";

    if (!target)
    {
      return "null";
    }

    var typeName = target.constructor.name;
    var isParentPrefab = false;
    if (typeName == "GameObject")
    {
      if (target.prefabParent)
      {
        if (target.prefabParent == target)
        {
          isParentPrefab = true;
        }
        else 
        {
          //If it has a prefab Parent (and it's not the prefab parent itself)
          //we return null since we will be loading this from the prefab.
          return "null";
        }
      }
    }

    //Shortcut    
    var nl = this.nl;

    data += "{" + nl.Tab();


    data += "\"t\":\"" + typeName + "\"";
    

    var propValues: Map<string, any> = this.GetPropValues(target, true);
    propValues.forEach((value: any, key: string) => {
      //Too hacky?
      if (this.isSerializingPrefab)
      {
        //If it's the Prefab
        if (isParentPrefab && key == "name")
        {
          value = target.prefabName;
        }
      }
      data += "," + nl;
      data += "\"" + key + "\"" + ":";
      if (value === null || value === undefined) {
        data += "null";
      } else if (value instanceof Array) {
        data += "[" + nl.Tab();
        for (var j: number = 0; j < value.length; j++) {
          data += this.GetPropertyJSON(value[j]);
          if (j < value.length - 1) {
            data += "," + nl;
          }
        }
        data += (nl.ShiftTab() + "]");
      }
      else if (typeof value == 'number' || typeof value == 'boolean') {
        data += value;
      }
      else if (typeof value == 'string') {
        data += "\"" + value + "\"";
      } else {
        //Assuming everything can be processed.  
        data += nl + this.GetPropertyJSON(value);
      }
    });

    data += nl.ShiftTab() + "}";

    return data;
  }
}

class NextLine {

  private nl: string = "\n";

  private tab = "   ";

  constructor(_prettyPrint: boolean) {
    this.prettyPrint = _prettyPrint;
  }


  prettyPrint: boolean = true;

  ShiftTab(): string {
    if (this.nl.endsWith(this.tab)) {
      this.nl = this.nl.substring(0, this.nl.length - this.tab.length);
    }
    return this.toString();
  }

  Tab(): string {
    this.nl += this.tab;
    return this.toString();
  }

  toString(): string {
    if (this.prettyPrint) {
      return this.nl;
    }

    return "";
  }
}

function SerializeField(target: any, propertyKey: string) {
  Serializer.RegisterSerialized(target, propertyKey);
}

function HideInInspector(target: any, propertyKey: string) {
  Serializer.RegisterHideInInspector(target, propertyKey);

}
                    [BaseObject] => //script:BaseObject
class BaseObject extends Object
{
    public name : string;
}
                    [EditorSystem] => //script:EditorSystem

class EditorSystem
{
  constructor ()
  {
    UnionEditor.editorSystems.push(this);
  }

  OnGUI ()
  {

  }
}
                    [Mathf] => class Mathf
{
  //TODO Optimize?
  static get Rad2Deg () : number
  {
    return 180 / Math.PI;
  }

  static get Deg2Rad () : number
  {
        return Math.PI / 180;
  }

  static Clamp (value : number, min : number, max : number)
  {
    return Math.min(Math.max(value, min), max);
  }

  static Min (value : number, min : number)
  {
    return Math.min(value, min);
  }

  static Max (value : number, min : number)
  {
    return Math.max(value, min);
  }

  static MoveTowards (current : number, target : number, maxDelta : number) : number
  {
    if (current < target - maxDelta)
    {
      return current + maxDelta;
    } else if (current > target + maxDelta)
    {
      return current - maxDelta;
    }
    return target;
  }
}
                    [SceneObject] => class SceneObject extends BaseObject {
    prefabParent: GameObject;

    public get markedForDestroy(): boolean {
        return this._markedForDestroy || this.gameObject._markedForDestroy;
    };

    public set markedForDestroy(value: boolean) {
        this._markedForDestroy = value;
    }

    private _markedForDestroy: boolean = false;

    public GetType(): string {
        return this.constructor.name;
    }

    public get transform(): Transform {
        return this.gameObject.transform;
    }

    public get renderer(): Renderer {
        return this.gameObject.renderer;
    }

    public get gameObject(): GameObject {
        return this._gameObject;
    }

    private _gameObject: GameObject;


    InternalPreAwake(go: GameObject) {
        SceneManager.MarkSceneAsDirty();
        this._gameObject = go;
        SceneManager.GetActiveScene().newObjs.push(this);
    }

    InternalAwake(go: GameObject) {
        if (Engine.isPlaying || Engine.IsEditorType(this)) {
            //User implemented so needs a try-catch
            try {
                this.Awake();
            } catch (e) {
                Debug.Log(e);
            }
        }
    }

    Awake() { }

    InternalStart() {
        if ((Engine.isPlaying || Engine.IsEditorType(this)) && !this.markedForDestroy) {
            try {
                this.Start();
            } catch (e) {
                Debug.Log(e, true);
            }
        }
    }

    Start() { }

    InternalUpdate() {
        if ((Engine.isPlaying || Engine.IsEditorType(this)) && !this.markedForDestroy) {
            try {
                this.Update();
            } catch (e) {
                Debug.Log(e, true);
            }
        }
    }

    Update() { }

    InternalDestroy() {
        if (!Engine.isPlaying) {
            SceneManager.MarkSceneAsDirty();
        }

        if (Engine.isPlaying || Engine.IsEditorType(this)) {
            try {
                this.OnDestroy();
            } catch (e) {
                Debug.Log(e);
            }
        }
        //Remove from components array
        const index = this.gameObject.components.indexOf(this);
        if (index > -1) {
            this.gameObject.components.splice(index, 1);
        }
    }

    OnCollisionEnter(other: Collision) { }

    OnTriggerEnter(otherCollider: Collider) { }

    OnTriggerStay(otherCollider: Collider) { }

    OnTriggerExit(otherCollider: Collider) { }

    OnDestroy() { }

    GetComponent<T extends Component>(objType: new () => T): T {
        for (var i: number = 0; i < this.gameObject.components.length; i++) {
            if (this.CheckType(this.gameObject.components[i], objType)) {
                var x: T = this.gameObject.components[i] as T;
                //No need to check if it's about to be destroyed.
                //if (!x.markedForDestroy)
                {
                    return x;
                }
            }
        }
        return null;
    }

    //Recursive function to check for base-classes as well
    CheckType(c: Component, objType: any): boolean {
        if (c.GetType() === objType.name) {
            return true;
        }

        //Stop looking after 'Component' 
        if (c.GetType() == "Component") {
            return false;
        }

        return this.CheckType(Object.getPrototypeOf(c), objType);
    }

}
                    [Component] => class Component extends SceneObject
{   
    public static isc : boolean = true;

    get name ()
    {
        return this.gameObject.name;
    }

    set name (value : string)
    {
        this.gameObject.name = value;
    }

   InternalPreAwake (go : GameObject)
   {
       go.components.push(this);  
       super.InternalPreAwake(go);       
   }
}

//Attribute that is used to Register a Script as a component
//This is used as an addition to all scripts that are already registered through the Script name
function RegisterAsComponent(target: Function) {
    Engine.scripts.push(target.prototype.constructor.name);
}
                    [GameObject] => class GameObject extends SceneObject {

    public static FindObjectOfType<T extends Component>(objType: new () => T): T {
        var objs = SceneManager.GetActiveScene().objs;
        for (var i: number = 0; i < objs.length; i++) {
            var comp = objs[i].GetComponent(objType);
            if (comp) {
                return comp;
            }
        }
        
        return null;
    }

    /**To find something*/
    public static Find(name: string): GameObject {
        var objs = SceneManager.GetActiveScene().objs;
        for (var i: number = 0; i < objs.length; i++) {
            if (objs[i].name == name) {
                return objs[i];
            }
        }
        return null;
    }


    @SerializeField
    public name: string;

    @SerializeField
    components: Component[];

    //TODO we should not refer to the prefab by name...
    @SerializeField
    prefabName: string;

    private get activeScene(): Scene {
        return SceneManager.GetActiveScene();
    }

    //TODO Optimize
    public get transform(): Transform {
        return this.GetComponent(Transform);
    }

    /** The renderer component is used to display the mesh. If no Renderer component exists, it's automatically added */
    public get renderer(): Renderer {
        if (this._renderer == null) {
            this._renderer = this.GetComponent(Renderer);
            if (this._renderer == null) {
                this._renderer = this.AddComponent(Renderer);
            }
        }
        return this._renderer;
    }

    private _renderer: Renderer;

    public get objectPhysics(): ObjectPhysics {
        if (this._objectPhysics == null) {
            this._objectPhysics = new ObjectPhysics();
            this._objectPhysics.Init(this);
        }
        return this._objectPhysics;
    }

    private _objectPhysics: ObjectPhysics;

    static Destroy(obj: SceneObject) {
        obj.markedForDestroy = true;
        Engine.toBeDestroyed.push(obj);
    }

    static Instantiate<T extends MonoBehaviour>(objType: string | (new () => T), startPosition: Vector3 = Vector3.Zero()) {
        //We first create an empty GameObject
        var gameObject = null;
        if (typeof objType === "string") {
            gameObject = Prefab.Create(objType + ".prefab");
            gameObject.transform.position = startPosition.clone();
            return gameObject;
        }
        else {
            gameObject = new GameObject();

            //We add this Component as a default one
            var defaultComponent = gameObject.AddComponent(objType);

            gameObject.name = "GameObject (" + defaultComponent.GetType() + ")";

            gameObject.transform.position = startPosition.clone();

            return defaultComponent;
        }
    }

    constructor() {
        super();
        this.InternalPreAwake(this);
        this.name = "GameObject";

        //When creating from a SerializedObject, it does not add these two default components.
        if (!Serializer.isCreatingGameObject) {
            //Every GameObject has a Transform & Renderer by default
            this.AddComponent(Transform);
            //this.AddComponent(Renderer);
        }
        
        this.InternalAwake(this);
    }

    AddComponent<T extends Component>(objType: (new () => T) | string): T {
        var newComponent: T = null;
        if (typeof objType == "string") {
            newComponent = eval("new " + objType + "()");
        } else {
            newComponent = new objType();
        }
        newComponent.InternalPreAwake(this);
        newComponent.InternalAwake(this);
        return newComponent;
    }

    OnCollisionEnter(otherCollider: Collision) {
        if (!this.markedForDestroy) {
            for (var i = 0; i < this.components.length; i++) {
                this.components[i].OnCollisionEnter(otherCollider);
            }
        }
    }

    OnTriggerEnter(otherCollider: Collider) {
        if (!this.markedForDestroy) {
            for (var i = 0; i < this.components.length; i++) {
                this.components[i].OnTriggerEnter(otherCollider);
            }
        }
    }

    OnTriggerStay(otherCollider: Collider) {
        if (!this.markedForDestroy) {
            for (var i = 0; i < this.components.length; i++) {
                this.components[i].OnTriggerStay(otherCollider);
            }
        }
    }

    OnTriggerExit(otherCollider: Collider) {
        if (!this.markedForDestroy) {
            for (var i = 0; i < this.components.length; i++) {
                this.components[i].OnTriggerExit(otherCollider);
            }
        }
    }

    InternalPreAwake(go: GameObject) {
        this.components = new Array();
        super.InternalPreAwake(go);
        this.activeScene.objs.push(this);
        this.activeScene.rootObjs.push(this);
    }

    InternalStart() {
        super.InternalStart();
    }

    InternalUpdate() {
        super.InternalUpdate();
        for (var i = 0; i < this.components.length; i++) {
            this.components[i].InternalUpdate();
        }
    }

    InternalDestroy() {

        for (var i = this.components.length - 1; i >= 0; i--) {
            this.components[i].InternalDestroy();
        }

        if (this._objectPhysics) {
            this._objectPhysics.OnDestroy();
        }

        //Remove from objects array
        const index = this.activeScene.objs.indexOf(this);
        if (index > -1) {
            this.activeScene.objs.splice(index, 1);
        }
        const index2 = this.activeScene.rootObjs.indexOf(this);
        if (index2 > -1) {
            this.activeScene.rootObjs.splice(index2, 1);
        }
    }
}
                    [MonoBehaviour] => class MonoBehaviour extends Component 
{

}

                    [MainGame] => 
ENABLE_EDITOR = true;
/*

class MainGame extends MonoBehaviour {

  //Start is called before the first frame update
  Awake() {
    print(this.GetComponent(Rigidbody).friction);
  }

  //Update is called once per frame
  Update() {

  }


}
/*
@RegisterAsComponent
class RotationTest extends MonoBehaviour {

  @SerializeField
  pla: number = 4;

  newParent = null;

  Start() {


  }

  Update() {
        Cursor.lockState = CursorLockMode.Locked;

    
    Cursor.lockState = CursorLockMode.Locked;

    var mouseXInput = Input.GetAxis("Mouse X");
    var mouseYInput = Input.GetAxis("Mouse Y");
    this.transform.Rotate(mouseYInput, mouseXInput, 0);
    
  }
}

@RegisterAsComponent
class Ground extends MonoBehaviour {
  Start() {
    this.GetComponent(BoxCollider).layerMask = 2 | 4 | 8 | 16;
  }
}

@RegisterAsComponent
class MyBox extends MonoBehaviour {

  @SerializeField
  triggerTime: number = 0;

  @SerializeField
  movement: number = 0;

  mat: BABYLON.StandardMaterial;

  @SerializeField
  meshOffset: Vector3 = new Vector3(0, 0, 0);

  m: BABYLON.Mesh;


  //Start is called before the first frame update
  Start() {

  }

  //Update is called once per frame
  Update() {
    // this.transform.eulerAngles.z += 1;
    //this.transform.position.y += this.speed;
    //this.transform.position.x += this.movement;
    if (!this.m) {
      this.m = this.renderer.GetMesh();
      // var v : BABYLON.Vector3 = new BABYLON.Vector3();
      // Vector3.VtoB(this.meshOffset, v);
      this.m.locallyTranslate(new BABYLON.Vector3(13, 0, 50));
      this.m.scaling = new BABYLON.Vector3(0.1, 0.1, 0.1);
    }

    if (this.m) {

      // this.m.translate(new BABYLON.Vector3(0,0,1), 10); 
      //);
    }
  }

  OnTriggerEnter(coll: Collider) {
    this.renderer.material.shader.diffuseColor = BABYLON.Color3.Green();
  }

  OnTriggerExit(coll: Collider) {
    this.renderer.material.shader.diffuseColor = BABYLON.Color3.Red();
  }

  OnTriggerStay(coll: Collider) {
    this.triggerTime += Time.deltaTime;
  }
}

/* */


                    [Camera] => @RegisterAsComponent
@ExecuteInEditMode
class Camera extends MonoBehaviour {
  public static main: Camera;


  public set mode(value: number) {
    this.bCam.mode = value;
  }

  public get mode(): number {
    return this.bCam.mode;
  }

  public orthoGraphicSize: number = 5;

  //TargetCamera because regular Camera can't update position/rotation
  public bCam: BABYLON.TargetCamera;

  //This is a bit hacky but that way the camera view doesn't reset in playmode
  Awake() {
    if (this.bCam != null) {
      this.bCam.dispose();
    }
    //var cam = new BABYLON.TargetCamera("MainCamera", new BABYLON.Vector3(0, 0, -10), Engine.scene);
    this.bCam = new BABYLON.TargetCamera("Camera", new BABYLON.Vector3(0, 10, 0), Engine.scene);
    if (ENABLE_EDITOR)
    {
      this.bCam.viewport = new BABYLON.Viewport(0,0,0.7, 0.5);
    } else {
      this.bCam.viewport = new BABYLON.Viewport(0, 0, 1, 1);
    }

    this.bCam.layerMask = 2;

    //Unshift because we dont want it to be at the top.
    Engine.scene.activeCameras.unshift(this.bCam);

    Camera.main = this;
  }

  Update() {
    this.bCam.position = this.transform.transformNode.absolutePosition;
    this.bCam.rotationQuaternion = this.transform.transformNode.absoluteRotationQuaternion;

    if (Camera.main.mode == BABYLON.Camera.ORTHOGRAPHIC_CAMERA) {
      var vert = this.orthoGraphicSize;
      var horz = vert * Engine.current.getScreenAspectRatio();
      this.bCam.orthoTop = vert;
      this.bCam.orthoBottom = -vert;
      this.bCam.orthoLeft = -horz;
      this.bCam.orthoRight = horz;
    }
  }

  OnDestroy() {
    this.bCam.dispose();
  }
}
                    [EditorCam] => class EditorCam  {

  cam : BABYLON.ArcRotateCamera;

  isAttached: boolean = false;


  constructor ()
  {
    this.cam = new BABYLON.ArcRotateCamera("ArcRotateCamera", 1, 0.8, 10, new BABYLON.Vector3(0, 0, 0), Engine.scene);
    
    this.cam.viewport = new BABYLON.Viewport(0,0.5,0.7, 0.5);  
      

    this.cam.attachControl(Engine.canvas, true);
    this.cam.layerMask = 2;
    this.cam.wheelPrecision = 10
    this.isAttached = true;

    Engine.scene.activeCameras.push(this.cam);
    Engine.scene.onBeforeRenderObservable.add((ev) => {
      this.Update();
    });

    
  }

  Update ()
  {
    //Detach on Update
    if (this.isAttached && (EditorUI.IsPointerOverEditor() || !Input.GetKey(KeyCode.Alt))) {
      this.cam.detachControl(Engine.canvas);
      this.isAttached = false;
    }
    else if (!this.isAttached && (!EditorUI.IsPointerOverEditor() && Input.GetKey(KeyCode.Alt))) {
      this.cam.attachControl(Engine.canvas, true);
      this.isAttached = true;
    }
    
    if (Input.GetKeyDown(KeyCode.F)) {
      var hasSelection = EditorSelection.GetSelection()?.length > 0;
      if (hasSelection)
      {
        this.cam.target = Vector3.VtoB(EditorSelection.GetCenterPoint(), new BABYLON.Vector3());
      }
    }
  }
}

                    [Renderer] => @RegisterAsComponent
@ExecuteInEditMode
class Renderer extends Component {

    public onChangedMesh: BABYLON.Observable<number>;

    private meshes: BABYLON.Mesh[] = new Array();
    @SerializeField
    private meshName: string = "";

    @SerializeField
    public materialName: string = "";

    @SerializeField
    public scale: number = 1;
    
    @SerializeField
    public castShadows : boolean = true;

    private prevCastShadows: boolean = true;
    
    @SerializeField
    public receiveShadows : boolean = false;
    
    private prevReceiveShadows: boolean = false;

    private _lastScale: number = 1;

    public material: Material;

    private textureName: string = "";

    private _isVisible: boolean = true;

    public get isVisible(): boolean {
        return this._isVisible;
    }

    public set isVisible(value: boolean) {
        this._isVisible = value;
        this.meshes.forEach((mesh) => {
            mesh.isVisible = value;
        });
    }

    public isLoadingMesh: boolean = false;

    public scaleFactor: BABYLON.Vector3 = BABYLON.Vector3.One();

    private isSprite: boolean = false;
    private spriteDisplayed: boolean = false;
    private lastMeshName: string = "";
    private lastMatName: string = "";


    private mergeMeshes: boolean = false;


    Awake() {
        //We removed the array of meshes for optimization
        this.onChangedMesh = new BABYLON.Observable();
    }

    Start() {
        this.CheckForMeshUpdate();
    }

    //Check for inspector update.
    CheckForMeshUpdate() {
        //If it was deserialized the meshName is set but LoadMesh is never called.
        if (this.meshName && this.meshName != this.lastMeshName) {
            this.LoadMesh(this.meshName, this.scale);
        }

        if (this.materialName && this.materialName != this.lastMatName && this.meshes.length > 0) {
            this.UpdateMaterial();
        }
    }

    Update() {
        if (this.prevCastShadows != this.castShadows)
        {
            this.UpdateShadows();
            this.prevCastShadows = this.castShadows;
        }
        if (this.prevReceiveShadows != this.receiveShadows)
        {
            this.UpdateShadows();
            this.prevReceiveShadows = this.receiveShadows;
        }

        this.CheckForMeshUpdate();
        this.InternalUpdateMeshes();
    }

    SetSprite(textureName: string) {
        this.SetMesh(BABYLON.Mesh.CreatePlane("sprite", 1, Engine.scene));

        //Sprites just have a unique material.
        this.material = new Material("Sprite");
        this.material.shader = new BABYLON.StandardMaterial("Mat", Engine.scene);
        this.material.shader.diffuseTexture = TextureSystem.GetSprite(textureName);

        this.material.shader.diffuseTexture.hasAlpha = true;
        this.material.shader.emissiveTexture = this.material.shader.diffuseTexture;
        this.material.shader.backFaceCulling = true;

        //Sprite is always a Single Mesh
        this.GetMesh().material = this.material.shader;

        this.isSprite = true;
        this.spriteDisplayed = false;
    }

    InternalUpdateMeshes() {
        if (this.isSprite) {
            if (!this.spriteDisplayed) {
                this.material.shader = this.meshes[0].material as BABYLON.StandardMaterial;
                var size = this.material.shader.diffuseTexture.getSize();
                //We need to check the width because the first time it is loaded this value might be 0
                if (size.width != 0) {
                    this.scaleFactor = new BABYLON.Vector3(size.width * 0.01, size.height * 0.01, 1);
                    this.spriteDisplayed = true;
                    this.UpdateScaling();
                }
            }
        } else {
            if (this.scale != this._lastScale) {
                this.scaleFactor = new BABYLON.Vector3(this.scale, this.scale, this.scale);
                this.UpdateScaling();
                this._lastScale = this.scale;
            }
        }

        //We can't use the mesh-callback since we need to reference this.transform
        if (this.isLoadingMesh) {
            //We merge the meshes!
            var meshes: BABYLON.Mesh[] = MeshLoader.GetMesh(this.meshName, this.textureName);
            if (meshes != null) {
                if (this.mergeMeshes) {
                    this.SetMesh(BABYLON.Mesh.MergeMeshes(meshes, true, true));
                } else {
                    this.SetMesh(meshes);
                }
                this.isLoadingMesh = false;
            } else {
                return;
            }
        }
    }

    UpdateScaling() {
        this.meshes.forEach((mesh: BABYLON.Mesh) => {
            mesh.scaling = this.scaleFactor ;//this.transform.transformNode.absoluteScaling.clone().multiply(this.scaleFactor);
        });
    }

    RemoveMesh() {
        if (this.meshes) {
            this.meshes.forEach((mesh: BABYLON.Mesh) => {
                try {
                    //print("Removing " + this.mesh.name + " to meshToObj with id " +this.mesh.uniqueId);
                    Engine.meshToObject.delete(mesh.uniqueId);

                    //This is false so when doing a scene reload the textures persist
                    mesh.dispose(false, false);

                    //If it's not a material from the material system...
                    if (!Material.Get(this.materialName)) {
                        mesh.material?.dispose();
                    }
                    this.material = null;
                } catch (e) {
                    console.warn("Failed to dispose mesh of " + (this.gameObject ? this.gameObject.name : "<Unknown>") + ". Has the array of meshes been modified?")
                }
            });
            this.meshes = new Array();
        }
    }

    SetMesh(newMesh: BABYLON.Mesh | BABYLON.Mesh[]) {
        //Just in case.
        this.RemoveMesh();

        if (newMesh instanceof BABYLON.Mesh) {
            this.SetSingleMesh(newMesh);
        } else {
            //TODO Implement this.
            newMesh.forEach((mesh: BABYLON.Mesh) => {
                this.SetSingleMesh(mesh);
            });
        }
        
        this.UpdateScaling() ;
        this.UpdateShadows ();
        this.onChangedMesh.notifyObservers(0);
    }

    private UpdateShadows () {
        this.meshes.forEach((mesh: BABYLON.Mesh) => {
            mesh.receiveShadows = this.receiveShadows;
            
            if (this.castShadows)
            {
                Shadows.AddCastingMesh(mesh);
            } else {
                Shadows.RemoveCastingMesh(mesh);
            }
            
        });
        
    }

    private SetSingleMesh(newMesh: BABYLON.Mesh) {
        this.meshes.push(newMesh);

        Engine.meshToObject.set(newMesh.uniqueId, this.gameObject);

        //Meshes get parented to their transform. A mesh should never be moved by itself, but instead the transform component should be used.
        newMesh.parent = (this.transform.transformNode);

        newMesh.isVisible = true;

        this.UpdateMaterial();
    }

    UpdateMaterial() {
        this.material = Material.Get(this.materialName);
        this.meshes[0].material = this.material.shader;
        this.lastMatName = this.materialName;
    }

    GetMesh(): BABYLON.Mesh {
        return this.meshes[0];
    }

    GetMeshes(): BABYLON.Mesh[] {
        return this.meshes;
    }

    LoadMesh(meshName: string, loadedScale: number = 1, textureName: string = null, mergeMeshes: boolean = true) {
        this.RemoveMesh();
        this.isLoadingMesh = true;
        this.meshName = meshName;
        this.lastMeshName = meshName;
        this.scaleFactor = new BABYLON.Vector3(loadedScale, loadedScale, loadedScale);
        this.mergeMeshes = mergeMeshes;

        if (textureName != null) {
            this.textureName = textureName;
        }
    }

    InternalDestroy() {
        super.InternalDestroy();
        this.RemoveMesh();
    }
}

                    [Physics] => //script:Physics

class Physics {
  public static gravity: BABYLON.Vector3 = new BABYLON.Vector3(0, -9.81, 0);
  // public static gravity2 : BABYLON.Vector3= new BABYLON.Vector3(0, -9.81, 0);

  static Init() {
    Engine.scene.onBeforePhysicsObservable.add(() => {

      Physics.BeforePhysicsUpdate();
    });
  }

  //If the other object is already triggering us, we don't need to retrigger


  private static BeforePhysicsUpdate() {
    var imposters = Engine.scene._physicsEngine.getImpostors();

    //We reset all trigger bools
    for (var i = 0; i < imposters.length; i++) {
      var op = (imposters[i].object as ObjectPhysics);
      op.triggerMap.forEach((value: boolean, key: number) => {
        op.triggerMap.set(key, false);
      });
    }

    for (var i = 0; i < imposters.length; i++) {
      var op = (imposters[i].object as ObjectPhysics);
      if (op.rb != null && op.rb.useGravity) {
        //Gravity
        imposters[i].applyForce(Physics.gravity.scale(imposters[i].mass), imposters[i].getObjectCenter());

        for (var j = 0; j < imposters.length; j++) {
          var op2 = (imposters[j].object as ObjectPhysics);
          if (op.coll && op2.coll && op.coll.boundsMesh.uniqueId != op2.coll.boundsMesh.uniqueId) {
            if (op.coll.isTrigger || op2.coll.isTrigger) {
              if (this.Intersects(op, op2)) {
                //Avoid double triggering of two rigidbodies
                if (!op2.triggerMap.get(op.coll.boundsMesh.uniqueId)) {
                  //If was already triggering...
                  if (op.triggerMap.has(op2.coll.boundsMesh.uniqueId)) {
                    try {
                      op.coll.gameObject.OnTriggerStay(op2.coll);
                    } catch (e) { Debug.Log(e); }
                    try {
                      op2.coll.gameObject.OnTriggerStay(op.coll);
                    } catch (e) { Debug.Log(e); }
                  } else {
                    try {
                      op.coll.gameObject.OnTriggerEnter(op2.coll);
                    } catch (e) { Debug.Log(e); }
                    try {
                      op2.coll.gameObject.OnTriggerEnter(op.coll);
                    } catch (e) { Debug.Log(e); }
                  }
                  op.triggerMap.set(op2.coll.boundsMesh.uniqueId, true);
                }
              }
            }
          }
        }
      }
    }


    for (var i = 0; i < imposters.length; i++) {
      var op = (imposters[i].object as ObjectPhysics);
      op.triggerMap.forEach((value: boolean, key: number) => {
        //If it was not set to true, it means it did not trigger this frame
        if (!value) {

          if (ObjectPhysics.colliderMap.has(key)) {
            var otherCollider = ObjectPhysics.colliderMap.get(key);
            //otherCollider is null when the PhysicsImpostor is not set up yet
            if (otherCollider) {
              try {
                op.coll.gameObject.OnTriggerExit(otherCollider);
              } catch (e) { Debug.Log(e); }
              try {
              otherCollider.gameObject.OnTriggerExit(op.coll);
              } catch (e) { Debug.Log(e); }
              op.triggerMap.delete(key);
            }
          }
        }
      });
    }
  }

  private static Intersects(op: ObjectPhysics, op2: ObjectPhysics): boolean {

    var box: number = BABYLON.PhysicsImpostor.BoxImpostor;
    var sphere: number = BABYLON.PhysicsImpostor.SphereImpostor;

    if (op.coll.GetImpostor() == box && op2.coll.GetImpostor() == box) {
      return (op.mesh.intersectsMesh(op2.mesh, true));
    }

    if (op.coll.GetImpostor() == box && op2.coll.GetImpostor() == sphere) {
      return this.BoxSphereIntersects(op, op2);
    }

    if (op.coll.GetImpostor() == sphere && op2.coll.GetImpostor() == box) {
      return this.BoxSphereIntersects(op2, op);
    }

    if (op.coll.GetImpostor() == sphere && op2.coll.GetImpostor() == sphere) {
      return this.SphereToSphere(op2.mesh.position, this.GetSphereRadius(op2.mesh), op.mesh.position, this.GetSphereRadius(op.mesh));
    }

    return false;
  }

  private static BoxSphereIntersects(op: ObjectPhysics, op2: ObjectPhysics): boolean {
    //We get the maxScale of the sphere, since we only allow the sphere to be perfectly round
    var radius = this.GetSphereRadius(op2.mesh);

    return this.SphereToAABB(op2.mesh.position, radius, op.mesh);
  }

  private static SphereToAABB(position: BABYLON.Vector3, radius: number, box: BABYLON.AbstractMesh): boolean {
    //Make the position relative to the box
    var p = position.subtract(box.position);

    //Rotate the resulting Vector by the inverted box rotation (to make the comparison axis aligned)
    p.rotateByQuaternionToRef(BABYLON.Quaternion.Inverse(box.rotationQuaternion), p);

    var s = box.scaling;

    //Getting the closest point is easy since it's axis aligned
    var closesPoint = new BABYLON.Vector3(
      Mathf.Clamp(p.x, -s.x * 0.5, s.x * 0.5),
      Mathf.Clamp(p.y, -s.y * 0.5, s.y * 0.5),
      Mathf.Clamp(p.z, -s.z * 0.5, s.z * 0.5));


    var dSqrd = BABYLON.Vector3.DistanceSquared(p, closesPoint);

    return radius * radius > dSqrd;
  }

  private static SphereToSphere(p1: BABYLON.Vector3, r1: number, p2: BABYLON.Vector3, r2: number): boolean {
    return BABYLON.Vector3.DistanceSquared(p1, p2) < (r1 + r2) * (r1 + r2);
  }

  public static OverlapSphere(position: Vector3, radius: number): Collider[] {
    var imposters = Engine.scene._physicsEngine.getImpostors();

    var colliders: Collider[] = new Array();

    var box: number = BABYLON.PhysicsImpostor.BoxImpostor;
    var sphere: number = BABYLON.PhysicsImpostor.SphereImpostor;

    for (var j = 0; j < imposters.length; j++) {
      var op = (imposters[j].object as ObjectPhysics);
      if (op.coll != null) {
        if (op.coll.GetImpostor() == box) {
          if (this.SphereToAABB(Vector3.VtoB(position, new BABYLON.Vector3()), radius, op.mesh)) {
            colliders.push(op.coll);
          }
        } else if (op.coll.GetImpostor() == sphere) {
          if (this.SphereToSphere(Vector3.VtoB(position, new BABYLON.Vector3()), radius, op.mesh.position, this.GetSphereRadius(op.mesh))) {
            colliders.push(op.coll);
          }
        }
      }
    }

    return colliders;
  }

  private static GetSphereRadius(mesh: BABYLON.AbstractMesh) {
    return Math.max(mesh.scaling.x, mesh.scaling.y, mesh.scaling.z) * 0.5;
  }




}
                    [ObjectPhysics] => 
//ObjectPhysics handling the connection of Colliders & Rigidbodies.
//Because Babylon combines all of that in a PhysicsImpostor, both colliders and rigidbodies affect the same ObjectPhysics
class ObjectPhysics implements BABYLON.IPhysicsEnabledObject {

  public static colliderMap: Map<number, Collider> = new Map();
  public triggerMap: Map<number, boolean> = new Map();

  public get isKinematic(): boolean {
    return this._isKinematic;
  }

  public set isKinematic(value: boolean) {
    this._isKinematic = value;
  }

  private _isKinematic: boolean;

  private go: GameObject;
  public physicsImpostor: BABYLON.PhysicsImpostor;

  private refresh: boolean = false;

  public get rb(): Rigidbody {
    if (this._rb == null || this._rb.markedForDestroy) {
      this._rb = this.go.GetComponent(Rigidbody);
    }
    return this._rb;
  }

  private _rb: Rigidbody;

  public get coll(): Collider {
    if (this._coll == null || this._coll.markedForDestroy) {
      this._coll = this.go.GetComponent(Collider);
    }
    return this._coll;
  }

  private _coll: Collider;

  private collidingImpostors: BABYLON.PhysicsImpostor[];

  Init(go: GameObject) {
    this.go = go;
    //The () => is essential to allow for UpdatePhysicsImpostor to reference it's class variables
    //this.go.transform.transformNode.onChangedMesh.add(() => {      

    //});

    Engine.scene.onBeforePhysicsObservable.add(() => {

    });

    Engine.scene.onAfterPhysicsObservable.add(() => {
      if (this.refresh) {
        this.refresh = false;
        this.UpdatePhysicsImpostor();
      }
    });
  }

  public MarkForUpdate() {
    this.refresh = true;
  }

  TryDisposePhysicsImpostor() {
    //We have to remove the old physicsImposter
    if (this.physicsImpostor != null) {
      if (this.coll) {
        ObjectPhysics.colliderMap.delete(this.coll.boundsMesh.uniqueId);
      }
      this.physicsImpostor.dispose();
      this.physicsImpostor = null;
    }
  }

  UpdatePhysicsImpostor() {
    this.TryDisposePhysicsImpostor();

    //This is usually just when the GameObject is being Destroyed.
    if (this.go.markedForDestroy || this.go.transform == null) {
      return;
    }

    //Justs a Rigidbody collides with nothing
    var imposterType = BABYLON.PhysicsImpostor.NoImpostor;
    if (this.coll != null) {
      imposterType = this.coll.GetImpostor();
    }

    var mass = this.rb ? this.rb.mass : 0;

    this.physicsImpostor = new BABYLON.PhysicsImpostor(this,
      imposterType,
      //Set to mass here so setMass(0) actually updates
      { mass: mass, restitution: 1},
      Engine.scene);

    //We have to call this setter once in order for friction to work...
    this.physicsImpostor.friction = 0.5;


    if (this.coll != null && this.rb != null) {
      this.collidingImpostors = Engine.scene._physicsEngine.getImpostors();
      this.physicsImpostor.registerOnPhysicsCollide(this.collidingImpostors, (main, collided) => {
        this.InternalOnCollide(collided);
      });
    }


    if (this.coll != null) {
      this.coll.UpdateValuesToImpostor();
      ObjectPhysics.colliderMap.set(this.coll.boundsMesh.uniqueId, this.coll);
    }

    if (this.rb != null) {
      this.rb.UpdateValuesToImpostor();
    }
  }

  InternalOnCollide(collided: BABYLON.PhysicsImpostor) {
    var otherObjPhysics = (collided.object as ObjectPhysics);
    var otherCollider: Collider = otherObjPhysics.coll;
    if (!this.coll || !otherObjPhysics.coll)
      return;

    if (this.coll.isTrigger || otherCollider.isTrigger) {
      //OnTriggerEvents are handled in the Physics class
    }
    else {
      var c1 = new Collision();
      c1.collider = otherCollider;
      c1.gameObject = otherCollider.gameObject;
      c1.transform = otherCollider.transform;
      c1.rigidbody = otherCollider.GetComponent(Rigidbody);

      var myVel = this.rb ? this.rb.velocity : new Vector3();
      var theirVel = c1.rigidbody ? c1.rigidbody.velocity : new Vector3();
      c1.relativeVelocity = new Vector3(myVel.x - theirVel.x, myVel.y - theirVel.y, myVel.z - theirVel.z);

      var c2 = new Collision();
      c2.collider = this.coll;
      c2.gameObject = this.go;
      c2.transform = this.go.transform;
      c2.rigidbody = this.rb;
      c2.relativeVelocity = new Vector3(-c1.relativeVelocity.x, -c1.relativeVelocity.y, -c1.relativeVelocity.z);
      try {
        this.go.OnCollisionEnter(c1);
      } catch (e) {
        Debug.Log(e);
      }
      try {
        otherCollider.gameObject.OnCollisionEnter(c2);
      } catch (e) {
        Debug.Log(e);
      }
    }
  }

  OnDestroy() {
    this.TryDisposePhysicsImpostor();
  }

  get mesh(): BABYLON.AbstractMesh {
    return this.go.transform.transformNode;
  }

  get position(): BABYLON.Vector3 {
    return this.mesh.position;
  }

  set position(value: BABYLON.Vector3) {
    this.mesh.position = value;
  }

  get rotationQuaternion(): BABYLON.Quaternion {
    return this.mesh.rotationQuaternion;
  }

  set rotationQuaternion(value: BABYLON.Quaternion) {
    this.mesh.rotationQuaternion = value;
  }

  get scaling(): BABYLON.Vector3 {
    return this.mesh.scaling;
  }

  set scaling(value: BABYLON.Vector3) {
    this.mesh.scaling = value;
  }

  getBoundingInfo(): BABYLON.BoundingInfo {
    return this.mesh.getBoundingInfo();
  }

  computeWorldMatrix(force?: boolean): BABYLON.Matrix {
    return this.mesh.computeWorldMatrix(force);
  }

  getVerticesData(kind: string): BABYLON.FloatArray {
    return this.mesh.getVerticesData(kind);
  }

  getAbsolutePivotPoint(): BABYLON.Vector3 {
    return this.mesh.getAbsolutePivotPoint();
  }

  getAbsolutePosition(): BABYLON.Vector3 {
    return this.mesh.getAbsolutePosition();
  }

  rotate(axis: BABYLON.Vector3, amount: number, space?: BABYLON.Space): BABYLON.TransformNode {
    return this.mesh.rotate(axis, amount, space);
  }

  translate(axis: BABYLON.Vector3, distance: number, space?: BABYLON.Space): BABYLON.TransformNode {
    return this.mesh.translate(axis, distance, space);
  }

  setAbsolutePosition(absolutePosition: BABYLON.Vector3): BABYLON.TransformNode {
    return this.mesh.setAbsolutePosition(absolutePosition);
  }

  getClassName(): string {
    return this.mesh.getClassName();
  }
}

class Collision {
  collider: Collider;
  rigidbody: Rigidbody;
  transform: Transform;
  gameObject: GameObject;
  relativeVelocity: Vector3;
}
                    [Rigidbody] => @RegisterAsComponent
class Rigidbody extends Component {

  private _vel: Vector3 = new Vector3();
  private _angularVel : Vector3 = new Vector3();

  private get objectPhysics(): ObjectPhysics {
    return this.gameObject.objectPhysics;
  }

  //region mass
  public get mass(): number {
    return this._mass;
  }

  public set mass(value: number) {
    //Setting the mass to 0 will break the physics
    if (value === 0) {
      value = 0.00001;
    }

    this._mass = value;
    this.UpdateMass();
  }

  get velocity(): Vector3 {
    if (this.objectPhysics.physicsImpostor)
    {
      Vector3.BtoV(this.objectPhysics.physicsImpostor.getLinearVelocity(), this._vel);
    }
    return this._vel;
  }

  set velocity(value: Vector3) {

    this._vel = value;
    this._vel.onChange.add(() => {
      if (this.objectPhysics.physicsImpostor)
      {
        this.objectPhysics.physicsImpostor.setLinearVelocity(Vector3.VtoB(this._vel, new BABYLON.Vector3()));
      }
    });

    this._vel.onChange.notifyObservers(null);
  }

  get angularVelocity(): Vector3 {
    if (this.objectPhysics.physicsImpostor)
    {
      Vector3.BtoV(this.objectPhysics.physicsImpostor.getAngularVelocity(), this._angularVel);
    }
    return this._angularVel;
  }

  set angularVelocity(value: Vector3) {

    this._angularVel = value;
    this._angularVel.onChange.add(() => {
      if (this.objectPhysics.physicsImpostor)
      {
        this.objectPhysics.physicsImpostor.setAngularVelocity(Vector3.VtoB(this._angularVel, new BABYLON.Vector3()));
      }
    });
    this._angularVel.onChange.notifyObservers(null);
  }

  @SerializeField
  private _mass: number = 10;
  //endregion

  //region restitution
  
  public get restitution(): number {
    return this._restitution;
  }

  public set restitution(value: number) {
    if (this.objectPhysics.physicsImpostor && this.objectPhysics.physicsImpostor.restitution != value) {
      this.objectPhysics.physicsImpostor.restitution = value;
    }
    this._restitution = value;
  }
  
  @SerializeField
  private _restitution: number = 0.5;
  
  //endregion

  //region friction
  public get friction(): number {
    return this._friction;
  }

  public set friction(value: number) {
    //Multiply by 10 to have similar numbers as before.
    if (this.objectPhysics.physicsImpostor != null) {
      this.objectPhysics.physicsImpostor.friction = value * 10;
    }
    this._friction = value;
  }

  @SerializeField
  private _friction: number = 0.5;
  //endregion

  //region isKinematic
  public get isKinematic(): boolean {
    return this._isKinematic;
  }

  public set isKinematic(value: boolean) {

     //Only update when changed.
    if (this._isKinematic != value)
    {
      this._isKinematic = value;
      //We check for the impostor since the setter in objectPhysics needs it
      if (this.objectPhysics.physicsImpostor) {
        {
          this.UpdateMass();
          if (value) {
            //Reset velocity.
            this.objectPhysics.MarkForUpdate();
          }
        }
      }
    }

  }

  @SerializeField
  private _isKinematic: boolean = false;
  private _wasKinematic: boolean = false;
  //endregion

  UpdateMass() {
    if (this.objectPhysics.physicsImpostor) {
      this.objectPhysics.physicsImpostor.mass = this.isKinematic ? 0 : this._mass;
    }
  }

  //Works! Cannon.js had to be modified to not calculate friction based on gravity
  useGravity: boolean = true;


  Awake() {
    this.objectPhysics.MarkForUpdate();
  }

  Update() {
    if (this._isKinematic != this._wasKinematic) {
      this.isKinematic = this._isKinematic;
      this._wasKinematic = this._isKinematic;
    }
    this.restitution = this.restitution;
    this.friction = this.friction;
  }

  OnDestroy() {

    this.objectPhysics.MarkForUpdate();

  }

  //When the mesh changes, we need to recreate the Impostor
  //With a Rigidbody, we only need to change some values.
  UpdateValuesToImpostor() {
    //force setters
    this.mass = this.mass;
    this.restitution = this.restitution;

    this.friction = this.friction;
    this.isKinematic = this.isKinematic;
  }
}

                    [Transform] => @ExecuteInEditMode
class Transform extends Component {    

    //This is a mesh because the Gizmo system does not like TransformNodes
    transformNode : BABYLON.AbstractMesh;

    @SerializeField
    private _position : Vector3;
     
    @SerializeField
    private _eulerAngles : Vector3;
    
    @SerializeField
    private _scale : Vector3;

    private _absolutePosition : Vector3 = new Vector3();
    private _absoluteScale : Vector3= new Vector3();
    private _absoluteRotation : Quaternion= new Quaternion();
    private _absoluteEulerAngles : Vector3 = new Vector3();
    private _absoluteRadians : Vector3 = new Vector3();

    @SerializeField
    uniqueId : number = -1;

    //ALL rotation is handled through the _rotation Quaternion
    //E.g. if Radians are changed, Radians > EulerAngles > Quaternion > Mesh
    //It has to be like that since Rigidbodies in Babylon set the Quaternion rotation
    //After the Quaternion has been set, this is the only way to rotate objects in Babylon
    private _radians : Vector3;
    private _rotation : Quaternion;

    private _children : GameObject[] = new Array();

    Awake ()
    {
        this.transformNode = (new BABYLON.Mesh(this.gameObject.name, Engine.scene));
        
        Engine.meshToObject[this.transformNode.uniqueId] = this.gameObject;
        this.transformNode.rotationQuaternion = new BABYLON.Quaternion();
        this.localPosition = this._position ? this._position.clone() : Vector3.Zero(); 
        this.localScale = this._scale ? this._scale.clone() : Vector3.One();
        var rot = this._eulerAngles ? this._eulerAngles.clone() : Vector3.Zero();
        
        this.localRotation = new Quaternion(0,0,0,0); 
        this.localEulerAngles = rot;
    }

    Start ()
    {
        //When saving the scene, all uniqueId's are stored.
        //When restoring a scene, the uniqueId comes back
        if (SceneManager.isReloading)
        {            
            this.transformNode.uniqueId = this.uniqueId;
        } else {
            this.uniqueId = this.transformNode.uniqueId;
        }
    }

    
    Update ()
    {
        //This seems redundant but it's calling the setter for all these variables
        //Because our inspector and serializer use the private variables, this needs to be done
        //This could be optimized (only selected + when serialized as scene) but is a minor performance overhead.
        this.transform.localPosition;
        this.transform.localEulerAngles;
        this.transform.localScale;
    }


    get parent() : Transform
    {
        return this._parent;
    }

    //ONLY for serialization...
    //getter only, since these will be set when Transforms are added automatically
    @SerializeField
    @HideInInspector
    public get ser_children () : GameObject[] 
    {
        return this._ser_children;
    }

    public set ser_children(value : GameObject[])
    {
       //this._ser_children = new Array();
    }

    public GetAllChildrenRecursively () : GameObject []
    {
        var arr = new Array();
        this.AddChildren(arr, this.ser_children);
        return arr;
    }

    private AddChildren (arr : GameObject[], children : GameObject[])
    {
        children.forEach(t => {
            arr.push(t);
            this.AddChildren(arr, t.transform.ser_children);
        });
    }

    private _ser_children : GameObject[] = new Array();

    GetSiblingIndex() : number
    {
        var c = this.parent ? this.parent.ser_children : SceneManager.GetActiveScene().rootObjs;

        return c.indexOf(this.gameObject);
    }

    SetSiblingIndex(index : number)
    {
        var c = this.parent ? this.parent.ser_children : SceneManager.GetActiveScene().rootObjs;
        index = Mathf.Clamp(index, 0, c.length);

        const oldIndex = c.indexOf(this.gameObject);
        //Remove from old position
        c.splice(oldIndex, 1);
        
        //Add to new position
        c.splice(index-1, 0, this.gameObject );
            
        
    }

    public SetParent(parent : Transform, worldPositionStays : boolean = true)
    {
        if (this.transform == parent)
        {
            Debug.Log("Can't set " + this.name + " to it's own parent.");
            return;
        }
        //We remove the object from the previous childrern list.
        if (this._parent)
        {
            var x = this._parent.ser_children.indexOf(this.gameObject);
            this._parent.ser_children.splice(x, 1);
        } else {
            var x = SceneManager.GetActiveScene().rootObjs.indexOf(this.gameObject);
            SceneManager.GetActiveScene().rootObjs.splice(x, 1);
        }
        this._parent = parent;

        if (!this._parent)
        {
            SceneManager.GetActiveScene().rootObjs.push(this.gameObject);
        }

        if (parent?.transformNode.nonUniformScaling)
        {
            Debug.Log(`Parented ${this.name} to non-uniformly scaled ${parent.name}. This is allowed but not recommended as it can lead to strange distortions.`);
        }

        if (worldPositionStays)
        {
            this.transformNode.setParent(parent?.transformNode);
        } else {
            this.transformNode.parent = parent?.transformNode;
        }

        //This array is auto populated when serializing.
        if (!Serializer.isSettingParent)
        {
           parent?.ser_children.push(this.gameObject);
        }
    }

    private _parent : Transform;


/*
Position, rotation and scale are all stored in the mesh. The Transform component
is nothing but a wrapper for the user. It will allow the user to easily update
the information and set rotation through eulerAngles or radians.
*/

/*
The event listener system for position, scale and rotation is designed
to pick up on changes on the x,y,z components of these Vectors. Through that,
users can change one axis of a Vector, which now automatically updates the information
in the game.
*/

// #region position
    get position () : Vector3
    {
        Vector3.BtoV(this.transformNode.absolutePosition, this._absolutePosition);  
        return this._absolutePosition;
    }

    set position (value : Vector3)
    {
        this._absolutePosition = value;

        this._absolutePosition.onChange.clear();

        this._absolutePosition.onChange.add(() => 
        {
            this.transformNode.setAbsolutePosition(Vector3.VtoB(this._absolutePosition, new BABYLON.Vector3()));
        });
        this._absolutePosition.onChange.notifyObservers(null);    
    }

    get localPosition(): Vector3 {    
        Vector3.BtoV(this.transformNode.position, this._position);  
        return this._position;
    }

    set localPosition(value: Vector3) {
        
        this._position = value;

        this._position.onChange.clear();

        this._position.onChange.add(() => 
        {
            Vector3.VtoB(this._position, this.transformNode.position);
        });
        this._position.onChange.notifyObservers(null);    
    }

// #endregion    


//#region scale
    get lossyScale() :Vector3
    {
        Vector3.BtoV(this.transformNode.absoluteScaling, this._absoluteScale);
        return this._absoluteScale;        
    }

    get localScale(): Vector3 {

        Vector3.BtoV(this.transformNode.scaling, this._scale);
        return this._scale;
    }

    set localScale(value: Vector3) {
        this._scale = value;

        this._scale.onChange.clear();


        this._scale.onChange.add(() => 
        {
            Vector3.VtoB(this._scale, this.transformNode.scaling);
        });
        this._scale.onChange.notifyObservers(null);
    }

//#endregion


//#region rotation

    get rotation () : Quaternion
    {
        return Quaternion.BtoQ(this.transformNode.absoluteRotationQuaternion, this._absoluteRotation);
    }

    get eulerAngles () : Vector3
    {
        return Quaternion.ToEulerAngles(this.rotation, this._absoluteEulerAngles);
    }

    get radians () : Vector3
    {
        //get the eulerAngles
        this.eulerAngles;
        this._radians.x = this._absoluteEulerAngles.x * Mathf.Rad2Deg;
        this._radians.y = this._absoluteEulerAngles.y * Mathf.Rad2Deg;
        this._radians.z = this._absoluteEulerAngles.z * Mathf.Rad2Deg;
        return this._radians;
    }
    
    get localEulerAngles(): Vector3 {
        Quaternion.ToEulerAngles(this.localRotation, this._eulerAngles);     
    
        return this._eulerAngles;
    }

    set localEulerAngles(value: Vector3) {
        
        this._eulerAngles = value;

        this._eulerAngles.onChange.clear();

        this._eulerAngles.onChange.add((index : number) => 
        {  
            //The x eulerAngle needs to be looked at!
            this.localRotation = Quaternion.Euler(this._eulerAngles.x, this._eulerAngles.y, this._eulerAngles.z, this._rotation);
        });
        
        this._eulerAngles.onChange.notifyObservers(null);
    }
   

    //Radians are untested.
    get localRadians () : Vector3
    {
        this._radians.x = this.localEulerAngles.x * Mathf.Rad2Deg;
        this._radians.y = this.localEulerAngles.y * Mathf.Rad2Deg;
        this._radians.z = this.localEulerAngles.z * Mathf.Rad2Deg;

        return this._radians;
    }

    set localRadians(value : Vector3)
    {
        this._radians = value;

        this._radians.onChange.clear();

        this._radians.onChange.add((index : number) => 
        {   
            switch (index)
            {                
                case 0:                
                    this.localEulerAngles.x = this._radians.x * Mathf.Rad2Deg;
                    break;
                case 1:
                    this.localEulerAngles.y = this._radians.y * Mathf.Rad2Deg;
                    break;
                case 2:
                    this.localEulerAngles.z = this._radians.z * Mathf.Rad2Deg;
                    break;
                default:
                    this.localEulerAngles.x = this._radians.x * Mathf.Rad2Deg;
                    this.localEulerAngles.y = this._radians.y * Mathf.Rad2Deg;
                    this.localEulerAngles.z = this._radians.z * Mathf.Rad2Deg;
                    break;
            }
        });
        this._radians.onChange.notifyObservers(null); 
    }
     
    get localRotation () : Quaternion
    {
        return Quaternion.BtoQ(this.transformNode.rotationQuaternion, this._rotation);
    }

    set localRotation(value : Quaternion)
    {
        this._rotation = value;

        this._rotation.onChange.clear();

        this._rotation.onChange.add(() => 
        {   
            Quaternion.QtoB(this._rotation, this.transformNode.rotationQuaternion);
        });
        this._rotation.onChange.notifyObservers(null); 
    }    

//#endregion

    public Rotate(xAxis: number, yAxis : number, zAxis:number, space?: BABYLON.Space): BABYLON.TransformNode {
        this.transformNode.rotate(new BABYLON.Vector3(1,0,0), xAxis * Mathf.Deg2Rad, space);
        this.transformNode.rotate(new BABYLON.Vector3(0,1,0), yAxis* Mathf.Deg2Rad, space);
        return this.transformNode.rotate(new BABYLON.Vector3(0,0,1), zAxis* Mathf.Deg2Rad, space);
    }

    public Translate (x : number, y : number, z : number, space?: BABYLON.Space)
    {
        this.transformNode.translate(new BABYLON.Vector3(1,0,0), x, space);
        this.transformNode.translate(new BABYLON.Vector3(0,1,0), y, space);
        this.transformNode.translate(new BABYLON.Vector3(0,0,1), z, space);
    }

    private DisposeCurrentTransformNode()
    {
        Engine.meshToObject.delete(this.transformNode.uniqueId);
        this.transformNode.dispose();
    }

    OnDestroy()
    {
        this.SetParent(null);
        this.DisposeCurrentTransformNode();
    }   
}
                    [Collider] => class Collider extends Component {
  //Only used for it's uniqueId
  public boundsMesh: BABYLON.TransformNode;

  protected bounds: BABYLON.BoundingInfo;
  protected boundingBoxLines : BABYLON.LinesMesh;


  protected get objectPhysics(): ObjectPhysics {
    return this.gameObject.objectPhysics;
  }

  //Overridden by Specific Colliders (e.g. BoxCollider)
  public GetImpostor(): number {
    return -1;
  }

  public get isTrigger(): boolean {
    return this._isTrigger;
  }

  public set isTrigger(value: boolean) {
    this._isTrigger = value;
    if (this.objectPhysics.physicsImpostor) {
      this.objectPhysics.physicsImpostor.physicsBody.collisionResponse = this.isTrigger ? 0 : 1;
    }
  }

  @SerializeField
  _isTrigger = false;

  wasTrigger = false;

  public get layer(): number {
    return this._layer;
  }

  public set layer(value: number) {
    this._layer = value;
    if (this.objectPhysics.physicsImpostor) {
      this.objectPhysics.physicsImpostor.physicsBody.collisionFilterGroup = value;
    }
  }

  @SerializeField
  _layer: number = 1;

  public get layerMask(): number {
    return this._layerMask;
  }

  public set layerMask(value: number) {
    this._layerMask = value;
    if (this.objectPhysics.physicsImpostor) {
      this.objectPhysics.physicsImpostor.physicsBody.collisionFilterMask = value;
    }
  }

  @SerializeField
  _layerMask: number = 1;


  Awake() {
    this.boundsMesh = new BABYLON.TransformNode("BoundsMesh", Engine.scene);

    var min = new BABYLON.Vector3(-0.5, -0.5, -0.5);
    var max = new BABYLON.Vector3(0.5, 0.5, 0.5);
    this.bounds = new BABYLON.BoundingInfo(min, max);
    //We make it a child to it scales along with transform
    this.transform.transformNode.setBoundingInfo(this.bounds);

    if (Engine.isPlaying) {
      this.objectPhysics.MarkForUpdate();

      //Scaling is not supported by PhysicsImpostors. Every time the scale changes we should update it
      this.transform.localScale.onChange.add((i: number) => {
        this.objectPhysics.UpdatePhysicsImpostor();
      });
    }
  }

  Update() {
    if (Engine.isPlaying) {
      //Pretty hacky solution to allow for Inspector Updates.
      if (this._isTrigger != this.wasTrigger) {
        this.isTrigger = this._isTrigger;
        this.wasTrigger = this._isTrigger;
      }
      this.layer = this.layer;
      this.layerMask = this.layerMask;
    }
    if (this.boundingBoxLines)
      this.boundingBoxLines.setEnabled(EditorSelection.IsSelected(this.gameObject, true)) ;
  }

  OnDestroy() {
    if (Engine.isPlaying) {
      this.objectPhysics.MarkForUpdate();
    }

    this.boundsMesh?.dispose();
    this.boundingBoxLines?.dispose();
  }

  //When the mesh changes, we need to recreate the Impostor
  //With a Rigidbody, we only need to change some values.
  UpdateValuesToImpostor() {
    this.objectPhysics.physicsImpostor.type = this.GetImpostor();
    this.isTrigger = this.isTrigger;
  }

}
                    [BoxCollider] => //script:BoxCollider
@ExecuteInEditMode
@RegisterAsComponent
class BoxCollider extends Collider
{

    @SerializeField
    _size : Vector3 = new Vector3(1,1,1);

    get size(): Vector3 {
        return this._size;
    }

    set size(value: Vector3) {
        this._size = value;
        this._size.onChange.add(() => 
        {
          this.ReconstructMeshLines ();
        });
        this._size.onChange.notifyObservers(null);
    }

    @SerializeField
    _center : Vector3 = new Vector3(0,0,0);

    get center(): Vector3 {
        return this._center;
    }

    set center(value: Vector3) {
        this._center = value;
        this._center.onChange.add(() => 
        {
          this.ReconstructMeshLines ();
        });
        this._center.onChange.notifyObservers(null);
    }

  ReconstructMeshLines ()
  {
    this.bounds.reConstruct(
      new BABYLON.Vector3(this.center.x + this.size.x * -0.5,this.center.y + this.size.y * -0.5, this.center.z + this.size.z * -0.5), 
      new BABYLON.Vector3(this.center.x + this.size.x * 0.5,this.center.y + this.size.y * 0.5, this.center.z + this.size.z * 0.5));
    this.transform.transformNode.setBoundingInfo(this.bounds);
    if (Engine.isPlaying)
    {
      this.objectPhysics.UpdatePhysicsImpostor();
    }
    //Drawing bounding boxes
    var myPoints = [
      this.bounds.minimum,
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.minimum.y, this.bounds.minimum.z),
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.minimum.y, this.bounds.maximum.z),
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.minimum.y, this.bounds.maximum.z),
      this.bounds.minimum,   
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.maximum.y, this.bounds.minimum.z),   
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.maximum.y, this.bounds.minimum.z),
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.minimum.y, this.bounds.minimum.z),
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.maximum.y, this.bounds.minimum.z),
      this.bounds.maximum,
      new BABYLON.Vector3(this.bounds.maximum.x, this.bounds.minimum.y, this.bounds.maximum.z),
      this.bounds.maximum,
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.maximum.y, this.bounds.maximum.z),
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.minimum.y, this.bounds.maximum.z),
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.maximum.y, this.bounds.maximum.z),
      new BABYLON.Vector3(this.bounds.minimum.x, this.bounds.maximum.y, this.bounds.minimum.z)
    ];

    if (this.boundingBoxLines)
      this.boundingBoxLines.dispose();

    this.boundingBoxLines = BABYLON.MeshBuilder.CreateLines("lines", {points: myPoints}, Engine.scene);
    this.boundingBoxLines.setParent(this.transform.transformNode);
    this.boundingBoxLines.color = BABYLON.Color3.Green();
    this.boundingBoxLines.position = BABYLON.Vector3.Zero();
    this.boundingBoxLines.rotation = BABYLON.Vector3.Zero();
    this.boundingBoxLines.scaling = BABYLON.Vector3.One();
  }

  Awake ()
  {
    super.Awake();
    this.size = this._size ? this._size.clone() : Vector3.One();
    this.center = this._center ? this._center.clone() : Vector3.Zero();
  }

  public GetImpostor () : number 
  {
    return BABYLON.PhysicsImpostor.BoxImpostor;
  }

  Update ()
  {
    super.Update();
    //Simple setter for SerializeField
    this.size;
    this.center;
  }

}
                    [SphereCollider] => @ExecuteInEditMode
@RegisterAsComponent
class SphereCollider extends Collider {

  @SerializeField
  radius: number = 1;
  private prevRadius: number = 0;

  //Increase to get more round gizmos
  private gizmoPrecision = 150;

  //This is a magical number to allow for the drawn gizmos to be the same as the radius in engine
  private radiusMultiplier = 75;

  public GetImpostor(): number {
    return BABYLON.PhysicsImpostor.SphereImpostor;
  }

  Update() {
    if (this.radius != this.prevRadius) {
      this.prevRadius = this.radius;
      this.ReconstructMeshLines();
    }
    super.Update();

  }

  ReconstructMeshLines() {
    this.bounds.reConstruct(
      new BABYLON.Vector3(-this.radius * 0.5,-this.radius * 0.5, -this.radius * 0.5), 
      new BABYLON.Vector3(this.radius * 0.5,this.radius * 0.5, this.radius * 0.5));
    this.transform.transformNode.setBoundingInfo(this.bounds);
    if (Engine.isPlaying)
    {
      this.objectPhysics.UpdatePhysicsImpostor();
    }

    this.boundingBoxLines?.dispose();
    this.boundingBoxLines = new BABYLON.LinesMesh("SphereBounds", Engine.scene);
    this.boundingBoxLines.parent = this.transform.transformNode;

    this.DrawCircle(0);
    this.DrawCircle(1);
    this.DrawCircle(2);
  }

  DrawCircle (axis : number)
  {
    var tess = this.gizmoPrecision;
    
    var radius = this.radius * this.radiusMultiplier;
    var pi2 = Math.PI * 2;
    var p = [];
    for (var i = 0; i <= tess; i++) {
        var ang = i * pi2 / tess;
        var c1 = radius * Math.cos(ang) / tess;
        var c2 = radius * Math.sin(ang) / tess;
        var x = axis == 0 ? 0 : c1;
        var y = axis == 1 ? 0 : c2;
        var z = axis == 2 ? 0 : axis == 1 ? c2 : c1;
        p.push(new BABYLON.Vector3(x,y,z));
    }

    
    var circle = BABYLON.Mesh.CreateLines('circle', p, Engine.scene);
    circle.parent = this.boundingBoxLines;
    circle.color = new BABYLON.Color3(0, 1, 0);

  }

  


}
                    [SceneManager] => //TODO rewrite this into a proper scene system
class SceneManager {

    public static onSceneLoaded : BABYLON.Observable<Scene> = new BABYLON.Observable();

    public static GetActiveScene(): Scene {
        return this.activeScene;
    }

    private static activeScene: Scene;

    static isReloading: boolean;

    private static activeSceneName: string;

    public static prefabLoaded : boolean;
    

    public static SaveCurrentScene() {
        if (Engine.isPlaying) {
            print("Can't save during playmode");
        } else {
            this.activeScene.Save();            
        }
    }

    static Init() {
        PixelPADEvents.onSceneClicked.add(name => {
            if (SceneManager.activeScene) {
                SceneManager.activeScene.Unload();
            }
            SceneManager.LoadScene(name);
        });

        PixelPADEvents.onSaveClicked.add (ev => {
            SceneManager.SaveCurrentScene();
        });
    }

    static ReloadCurrentScene() {

        if (this.activeScene != null) {
            var newName = this.activeScene.name;
            this.LoadScene(newName);
        }
        else {
            Debug.Log("No scene is currently loaded")
        }
    }

    public static LoadPrefab (name : string)
    {
        this.prefabLoaded = true;
        this.LoadSceneOrPrefab(name);
    }

    public static LoadScene(name: string) {
        this.prefabLoaded = false;
        this.LoadSceneOrPrefab(name);       
    }

    private static LoadSceneOrPrefab (name : string)
    {
        if (SceneManager.isReloading) {
            return;
        }

        EditorSelection.SetSelectedGameObject(null);
        if (this.activeScene != null) {
            this.activeScene.Unload();
        }
        this.activeSceneName = name;
        if (ENABLE_EDITOR)
        {
            UnionEditor.SetTitle((this.prefabLoaded? "Prefab: " : "Scene: ") + name);
        }
        //Using this bool so Scenes are only reloaded at the very end of a frame
        SceneManager.isReloading = true;
    }

    private static LoadSceneImmdiate(name: string) {
        SceneManager.isReloading = true;
        this.activeScene = new Scene();
        this.activeScene.name = name;
        
        var sceneJSONData = "";
        if (this.prefabLoaded)
        {
            sceneJSONData = getPrefab(name);
        } else {
            sceneJSONData = getScene(name);
        }

        //This loads all objects into the activeScene
        var sceneData: Scene = Serializer.FromJSON(sceneJSONData, false);
        /*
        //If the sceneData is null, this is probably an empty scene/prefab
        if (this.prefabLoaded && (!sceneData || sceneData.rootObjs.length == 0))
        {
            this.activeScene.rootObj = new GameObject();
            this.activeScene.name = Prefab.SimpleName(name);
        } else {
            this.activeScene.rootObj = sceneData.rootObjs[0];
        }        
        */    
        SceneManager.isReloading = false;
        SceneManager.onSceneLoaded.notifyObservers(this.activeScene);
    }

    public static MarkSceneAsDirty() {
        if (!Engine.isPlaying && !SceneManager.isReloading) {
            markSceneDirty();
        }
    }

    static Update() {
        if (SceneManager.isReloading) {
            this.LoadSceneImmdiate(this.activeSceneName);
            
        }
    }
}
                    [Scene] => //script:Scene
class Scene {
  public name: string;

  public objs: GameObject[];

  //Null for scenes, the single root object for prefabs
  public rootObj : GameObject;

  //The objects that have no parent
  @SerializeField
  public rootObjs : GameObject[] = new Array();

  //Any newly created GameObjects or Components
  public newObjs : SceneObject[]

  public constructor() {
    this.objs = new Array();
    this.newObjs = new Array();
  }

  public isDirty : boolean;

  public Save ()
  {
    var jsonData = Serializer.ToJSON(this, true);
    if (SceneManager.prefabLoaded)
    {
      savePrefab(this.name, jsonData);
    } else {
      saveScene(this.name, jsonData);  
    }      
    this.isDirty = false;    
  }

  public Unload(): void {
    for (var i = this.objs.length - 1; i >= 0; i--) {
      GameObject.Destroy(this.objs[i]);
    }
  }
}
                    [Light] => @RegisterAsComponent
@ExecuteInEditMode
class Light extends MonoBehaviour
{
  light :  BABYLON.ShadowLight;

  @SerializeField
  public intensity : number = .7;

 // @SerializeField
  public range : number = 5;

  rot : BABYLON.Vector3;


  Awake ()
  {
    this.light = new BABYLON.DirectionalLight("pointlight", new BABYLON.Vector3(-1, -2, -1), Engine.scene);
    Shadows.AddLight(this);
    
  }

  Update ()
  {
    Vector3.VtoB(this.transform.position, this.light.position);
    this.light.direction = this.transform.transformNode.forward.clone();
    this.light.intensity = this.intensity;
    this.light.range = this.range;
  }

  OnDestroy ()
  {
    Shadows.RemoveLight(this);
    this.light.dispose();
  }
}

                    [MeshLoader] => //Helps loading and caches meshes
class MeshLoader
{
    static enableLogging : boolean = true;
    static meshTaskMap : Map<string, BABYLON.MeshAssetTask>;

    //TODO move into base class
    static Log (msg)
    {
        if (MeshLoader.enableLogging)
        {
            print(msg)
        }
    }

    static Init ()
    {
        MeshLoader.meshTaskMap = new Map();
    }

    //Adds a meshTask to the map and starts loading it
    //NOTE: The loading happens asynchronously.
    static LoadMesh(name : string, textureName : string = "")
    {
        //TODO Optimize: Do we need a new assetsManager every time?
        var assetsManager = new BABYLON.AssetsManager(Engine.scene);
        assetsManager.useDefaultLoadingScreen = false;        

        var fileName = name;

        fileName = Engine.getFullName(name, FileType.Model);
        if (fileName == null)
        {
            print("Could not load mesh " + name + ". Are you sure it exists?");
            return;
        }
        textureName = Engine.getFullName(textureName, FileType.Texture);
        
        var url = Engine.getUrl();

        var meshTask = assetsManager.addMeshTask("Loading " + name, "", url, fileName);
        MeshLoader.meshTaskMap[name] = meshTask;

        MeshLoader.Log("Loading mesh " + url + name + "...");
        
        var mat = new BABYLON.StandardMaterial("Mat", Engine.scene);
        if (textureName != null && textureName != "")
        {
            mat.diffuseTexture = new BABYLON.Texture(url + textureName, Engine.scene);
        }
        
        assetsManager.onFinish = function (task) {
            for (var i = 0; i < meshTask.loadedMeshes.length; i++)
            {
                meshTask.loadedMeshes[i].isVisible = false;
                meshTask.loadedMeshes[i].material = mat;
            }
            MeshLoader.Log("Mesh " + name + " loaded successfully!");
        }
        assetsManager.load();
    }

    //Tries and gets the mesh for the different objects
    static GetMesh(name, textureName)
    {        
        //Bit hacky
        if (name == "box" || name == "sphere")
        {
            var defaultMesh = [];
            if (name == "box")
            {
                defaultMesh.push(BABYLON.Mesh.CreateBox(name, 1, Engine.scene));
            } else
            {
                defaultMesh.push(BABYLON.Mesh.CreateSphere(name, 8, 1, Engine.scene));
            }
            return defaultMesh;
        }


        //meshTaskToMap has a list of all loading/loading meshes
        //If it's not in there, it should be loaded at that time.
        var meshTask : BABYLON.MeshAssetTask = MeshLoader.meshTaskMap[name];

        if (meshTask == null)
        {
            this.LoadMesh(name, textureName);
            return null;
        }

        if (!meshTask.isCompleted)
        {
            return null;
        }

        //If the mesh has been loaded, we return a copy of it.
        //This ensures the mesh is only loaded once!
        var meshes = [...MeshLoader.meshTaskMap[name].loadedMeshes];
        for (var i = 0; i < meshes.length; i++)
        {
            meshes[i] = meshes[i].clone("Clone_" + i, null);
        }

        return meshes;
    }
}

                    [TextureSystem] => class TextureSystem
{
    static spriteToManager : Map<string, BABYLON.SpriteManager>;

    public static Init ()
    {
        TextureSystem.spriteToManager = new Map();
    }

    //TODO This should be cached
    public static GetSprite(shortName : string) : BABYLON.Texture
    {
        /*
        var manager : BABYLON.SpriteManager = SpriteSystem.spriteToManager[shortName];

        if (manager == null)
        {
            var url : string = Engine.getUrl() + Engine.getFullName(shortName, FileType.Texture);
            manager = new BABYLON.SpriteManager("spriteManager", url, 1, 1, Engine.scene);
            SpriteSystem.spriteToManager[shortName] = manager;
        }
        
        var sprite = new BABYLON.Sprite("sprite", manager);
        
        return sprite;
        */
        var url : string = Engine.getUrl() + Engine.getFullName(shortName, FileType.Texture);
        var texture = new BABYLON.Texture(url, Engine.scene);
        
        return texture;
    }
}
                    [Input] => class Input {
    private static codeToKeyState: Record<number, number>

    private static nameToKeyState: Record<string, number>

    private static indexToMouseButtonState : Record <number, number>

    private static mouseDelta : BABYLON.Vector2 = new BABYLON.Vector2();
    
    private static keyAxisRaw : BABYLON.Vector2 = new BABYLON.Vector2();

    private static keyAxis : BABYLON.Vector2 = new BABYLON.Vector2();

    private static sensitivity : number = 0.1;

    private static mouseSensitivity = 0.1;




    static Init() {
        //Stores all input keys
        //0 = down, 1 = held, 2 = up, 3 = none
        Input.codeToKeyState = {};
        Input.nameToKeyState = {};
        Input.indexToMouseButtonState = {}

        Engine.scene.actionManager = new BABYLON.ActionManager(Engine.scene);

        Engine.scene.actionManager.registerAction(new BABYLON.ExecuteCodeAction(BABYLON.ActionManager.OnKeyDownTrigger, function (evt) {
            if (Input.codeToKeyState[evt.sourceEvent.keyCode] != 1) {
                Input.codeToKeyState[evt.sourceEvent.keyCode] = 0;
                Input.nameToKeyState[evt.sourceEvent.key] = 0;
            }
        }));

        Engine.scene.actionManager.registerAction(new BABYLON.ExecuteCodeAction(BABYLON.ActionManager.OnKeyUpTrigger, function (evt) {
            Input.codeToKeyState[evt.sourceEvent.keyCode] = 2;
            Input.nameToKeyState[evt.sourceEvent.key] = 2;
        }));


        Engine.scene.onAfterRenderObservable.add((ev) => {
            //We reset this every frame because pointermove does not call when not moving
            this.mouseDelta.x = 0;
            this.mouseDelta.y = 0;
        });

        Engine.scene.onPointerObservable.add((pointerInfo) => {
        
            switch (pointerInfo.type) {
                case BABYLON.PointerEventTypes.POINTERMOVE:
                    this.mouseDelta.x = pointerInfo.event.movementX * this.mouseSensitivity;
                    this.mouseDelta.y = pointerInfo.event.movementY * this.mouseSensitivity;
                break;

                case BABYLON.PointerEventTypes.POINTERDOWN:
                   if (Input.indexToMouseButtonState[pointerInfo.event.button] != 1) {
                        Input.indexToMouseButtonState[pointerInfo.event.button] = 0 ;
                    }
                break;

                case BABYLON.PointerEventTypes.POINTERUP:
                    Input.indexToMouseButtonState[pointerInfo.event.button] = 2; 
                    
                break;
            }
        });

        
    }

    static Update() {

        this.ProcessAxes();

        for (let k in Input.codeToKeyState) {
            if (Input.codeToKeyState[k] == 0 || Input.codeToKeyState[k] == 2) {
                Input.codeToKeyState[k]++;
            }
        }

        for (let k in Input.nameToKeyState) {
            if (Input.nameToKeyState[k] == 0 || Input.nameToKeyState[k] == 2) {
                Input.nameToKeyState[k]++;
            }
        }

        for (let k in Input.indexToMouseButtonState) {
            if (Input.indexToMouseButtonState[k] == 0 || Input.indexToMouseButtonState[k] == 2) {
                Input.indexToMouseButtonState[k]++;
            }
        }
    }

    static ProcessAxes ()
    {
        if (this.GetKey(KeyCode.LeftArrow) || this.GetKey(KeyCode.A))
        {
            this.keyAxisRaw.x = -1;
        } else if (this.GetKey(KeyCode.RightArrow) || this.GetKey(KeyCode.D))
        {
            this.keyAxisRaw.x = 1;
        } else {
            this.keyAxisRaw.x = 0;
        }

        if (this.GetKey(KeyCode.UpArrow) || this.GetKey(KeyCode.W))
        {
            this.keyAxis.y = 1;
        }
        if (this.GetKey(KeyCode.DownArrow) || this.GetKey(KeyCode.S))
        {
            this.keyAxis.y = -1;
        }

        this.keyAxis.x = Mathf.MoveTowards(this.keyAxis.x, this.keyAxisRaw.x, this.sensitivity);
        this.keyAxis.y = Mathf.MoveTowards(this.keyAxis.y, this.keyAxisRaw.y, this.sensitivity);
    }

    static GetMouseButton (button : number) : boolean
    {
        return Input.indexToMouseButtonState[button] == 0 || Input.indexToMouseButtonState[button] == 1;;
    }

    static GetMouseButtonDown (button : number) : boolean
    {
        return Input.indexToMouseButtonState[button] == 0;
    }

    static GetMouseButtonUp (button : number) : boolean
    {
        return Input.indexToMouseButtonState[button] == 2;
    }

    static GetKey(key: string | KeyCode) {
        if (typeof key == "string") {
            return Input.nameToKeyState[key] == 0 || Input.nameToKeyState[key] == 1;
        }
        return Input.codeToKeyState[key] == 0 || Input.codeToKeyState[key] == 1;
    }

    static GetKeyDown(key: string | KeyCode) {
        if (typeof key == "string") {
            return Input.nameToKeyState[key] == 0;
        }
        return Input.codeToKeyState[key] == 0;
    }

    static GetKeyUp(key: string | KeyCode) {
        if (typeof key == "string") {
            return Input.nameToKeyState[key] == 2;
        }
        return Input.codeToKeyState[key] == 2;
    }

    static GetAxis(axisName: string) : number{
        switch (axisName) {
            case "Mouse X":
                return this.mouseDelta.x;
            case "Mouse Y":
                return this.mouseDelta.y;
            case "Horizontal":
                return this.keyAxis.x;
            case "Vertical":
                return this.keyAxis.y;
            default:
                Debug.Log(`Axisname ${axisName} does not exist! Currently the only options are Mouse X, Mouse Y, Horizontal & Vertical`);
            break;
        }
    }

}

enum KeyCode {
    Break = 3,
    Backspace = 8,
    Tab = 9,
    Clear = 12,
    Enter = 13,
    Shift = 16,
    Control = 17,
    Alt = 18,
    Pause = 19,
    CapsLock = 20,
    Hangul = 21,
    Hanja = 25,
    Escape = 27,
    Conversion = 28,
    NonConversion = 29,
    Space = 32,
    PageUp = 33,
    PageDown = 34,
    End = 35,
    Home = 36,
    LeftArrow = 37,
    UpArrow = 38,
    RightArrow = 39,
    DownArrow = 40,
    Select = 41,
    Print = 42,
    Execute = 43,
    PrintScreen = 44,
    Insert = 45,
    Delete = 46,
    Help = 47,
    Alpha0 = 48,
    Alpha1 = 49,
    Alpha2 = 50,
    Alpha3 = 51,
    Alpha4 = 52,
    Alpha5 = 53,
    Alpha6 = 54,
    Alpah7 = 55,
    Alpha8 = 56,
    Alpha9 = 57,
    Colon = 58,
    Semicolon = 59,
    LessThan = 60,
    Equals = 61,
    Beta = 63,
    CommercialAt = 64,
    A = 65,
    B = 66,
    C = 67,
    D = 68,
    E = 69,
    F = 70,
    G = 71,
    H = 72,
    I = 73,
    J = 74,
    K = 75,
    L = 76,
    M = 77,
    N = 78,
    O = 79,
    P = 80,
    Q = 81,
    R = 82,
    S = 83,
    T = 84,
    U = 85,
    V = 86,
    W = 87,
    X = 88,
    Y = 89,
    Z = 90,
    WindowsKey = 91,
    LeftCommand = 91,
    RightWindow = 92,
    RightCommand = 93,
    Sleep = 95,
    Keypad0 = 96,
    Keypad1 = 97,
    Keypad2 = 98,
    Keypad3 = 99,
    Keypad4 = 100,
    Keypad5 = 101,
    Keypad6 = 102,
    Keypad7 = 103,
    Keypad8 = 104,
    Keypad9 = 105,
    KeypadMultiply = 106,
    KeypadAdd = 107,
    KeypadPeriod = 108,
    KeypadSubtract = 109,
    DecimalPoint = 110,
    KeypadDivide = 111,
    F1 = 112,
    F2 = 113,
    F3 = 114,
    F4 = 115,
    F5 = 116,
    F6 = 117,
    F7 = 118,
    F8 = 119,
    F9 = 120,
    F10 = 121,
    F11 = 122,
    F12 = 123,
    F13 = 124,
    F14 = 125,
    F15 = 126,
    F16 = 127,
    F17 = 128,
    F18 = 129,
    F19 = 130,
    F20 = 131,
    F21 = 132,
    F22 = 133,
    F23 = 134,
    F24 = 135,
    F25 = 136,
    F26 = 137,
    F27 = 138,
    F28 = 139,
    F29 = 140,
    F30 = 141,
    F31 = 142,
    F32 = 143,
    NumLock = 144,
    ScrollLock = 145,
    AirplaneMode = 151,
    CircumFlex = 160,
    ExclamationMark = 161,
    ArabicSemicolon = 162,
    Hash = 163,
    DollarSign = 164,
    PageBackward = 166,
    PageForward = 167,
    Refresh = 168,
    Asterisk = 170,
}
                    [Random] => /**
The Random class can give you random values
 */
class Random
{
    static get value ()
    {
        return Math.random();
    }

    static Range (min : number, max : number, wholeNumber : boolean = false)
    {
        var r : number = (Math.random() * (max-min)) + min;
        if (wholeNumber)
        {
            return Math.floor(r);
        }
        return r;
    }
}
                    [BabylonExtensions] => //TODO Rework this class.
class Vector2 extends BABYLON.Vector2
{
    
}

class Vector3 
{
    public onChange : BABYLON.Observable<number>;

    constructor(x? : number, y? : number, z? : number) 
    {
        this.onChange = new BABYLON.Observable();
        this.x = x ? x : 0;
        this.y = y ? y : 0;
        this.z = z ? z : 0;
    }

    _x : number;
    _y : number;    
    _z : number;

    @SerializeField
    get x () : number
    {
        return this._x;
    }

    set x (value : number)
    {
        this._x = value
        this.onChange.notifyObservers(0);
    }

    @SerializeField
    get y () : number
    {
        return this._y;
    }

    set y (value : number)
    {
        this._y = value;
        this.onChange.notifyObservers(1);
    }
    
    @SerializeField
    get z () : number
    {
        return this._z;
    }
    
    set z (value : number)
    {
        this._z = value;
        this.onChange.notifyObservers(2);
    }

    public add(v : Vector3)
    {
        this.x += v.x;
        this.y += v.y;
        this.z += v.z;
    }

    public negate(v : Vector3)
    {
        this.x -= v.x;
        this.y -= v.y;
        this.z -= v.z;
    }

    static One () : Vector3
    {
        return new Vector3(1.0, 1.0, 1.0);
    }

    static Zero() : Vector3
    {
        return new Vector3(0.0, 0.0, 0.0);
    }

    public static Lerp (a : Vector3, b : Vector3, l : number) : Vector3
    {
        var v : Vector3 = a.clone();
        v.x = v.x + l * (b.x - v.x);
        v.y = v.y + l * (b.y - v.y);
        v.z = v.z + l * (b.z - v.z);
        return v;
    }

    clone () : Vector3
    {
        return new Vector3(this.x, this.y, this.z);
    }

    public static VtoB (v: Vector3, b : BABYLON.Vector3) : BABYLON.Vector3
    {
        b.x = v._x;
        b.y = v._y;
        b.z = v._z;
        return b;
    }

    public static BtoV  (b : BABYLON.Vector3, v : Vector3) : Vector3
    {
        v._x = b.x;
        v._y = b.y;
        v._z = b.z;
        return v;
    }

    toString ()
    {
        return "Vector3("+ this._x + "," + this._y + "," + this._z + ")";
    }

    public get magnitude () : number
    {
        return Math.sqrt(this.sqrMagnitude);
    }

    public get sqrMagnitude () : number
    {
        return this.x*this.x + this.y * this.y + this.z*this.z;
    }

    public get normalized () : Vector3
    {
        var m = 1.0 / this.magnitude;
        return new Vector3(this.x * m, this.y * m, this.z* m);
    }
}

class Quaternion 
{
    public onChange : BABYLON.Observable<number>;

    constructor(x? : number, y? : number, z? : number, w? : number) 
    {
        this.onChange = new BABYLON.Observable();
        this.x = x;
        this.y = y;
        this.z = z;
        this.w = w;
    }

    _x : number;
    _y : number;
    _z : number;
    _w : number;

    @SerializeField
    get x () : number
    {
        return this._x;
    }

    set x (value : number)
    {
        this._x = value
        this.onChange.notifyObservers(0);
    }

    @SerializeField
    get y () : number
    {
        return this._y;
    }

    set y (value : number)
    {
        this._y = value;
        this.onChange.notifyObservers(1);
    }

    @SerializeField
    get z () : number
    {
        return this._z;
    }
    
    set z (value : number)
    {
        this._z = value;
        this.onChange.notifyObservers(2);
    }

    @SerializeField
    get w () : number
    {
        return this._w;
    }
    
    set w (value : number)
    {
        this._w = value;
        this.onChange.notifyObservers(3);
    }    

    clone () : Quaternion
    {
        return new Quaternion(this.x, this.y, this.z, this.w);
    }

    toString ()
    {
        return "Quaternion("+ this._x + "," + this._y + "," + this._z + "," + this._w + ")";
    }

    public static QtoB (v: Quaternion, b : BABYLON.Quaternion) : BABYLON.Quaternion
    {
        b.x = v._x;
        b.y = v._y;
        b.z = v._z;
        b.w = v._w;
        return b;
    }

    public static BtoQ  (b : BABYLON.Quaternion, v : Quaternion) : Quaternion
    {
        v._x = b.x;
        v._y = b.y;
        v._z = b.z;
        v._w = b.w;
        return v;
    }

    public static Inverse (q : Quaternion)
    {
        return new Quaternion(-q.x, -q.y, -q.z, -q.w);
    }

    //TODO Can we do this math without Babylon?
    public static Euler (x : number, y : number, z : number, q? : Quaternion) : Quaternion
    {
        if (!q)
        {
            q = new Quaternion(0,0,0,0);
        }

        var newQuat = BABYLON.Quaternion.FromEulerAngles(x*Mathf.Deg2Rad,y*Mathf.Deg2Rad,z*Mathf.Deg2Rad);
        return Quaternion.BtoQ(newQuat, q);
    }

    static ToEulerAngles(q : Quaternion, v : Vector3) : Vector3
    {
        var b = new BABYLON.Quaternion();
        Quaternion.QtoB(q, b);

        var bv : BABYLON.Vector3 = b.toEulerAngles();
        bv.x *= Mathf.Rad2Deg;
        bv.y *= Mathf.Rad2Deg;
        bv.z *= Mathf.Rad2Deg;

        return Vector3.BtoV(bv, v);
    }
}


//The GameScene (hidden to user) creates all Game Related objects
class GameScene extends MonoBehaviour {

    Start()
    {
        this.renderer.isVisible = false;
    }

    Update()
    {

    }
}

                    [EditorHelpers] => class TestObject extends MonoBehaviour
{    
    public static main;

    public rot : Vector3;

    @SerializeField
    public rotateSpeed : number = 50;
        @SerializeField
    moveSpeed : number = 0;

    Start()
    {      
       // this.rot = new Vector3(Random.value, Random.value, Random.value);
      //  this.transform.eulerAngles = new Vector3(30,0,0);
    }

    Update()
    {
       // this.transform.position.y += this.moveSpeed * Time.deltaTime;
    }

    OnTriggerEnter (c : Collider)
    {
        print("TRIGGER: " + this.name + " + " + c.name);
    }
}

class FPSCounter extends MonoBehaviour
{
    nFrameUpdateInterval : number = 10;

    text : TextLabel;
    average : number = 0;
    nFramesSinceUpdate : number = 0;

    Start()
    {
        this.text = this.gameObject.AddComponent(TextLabel);
        this.transform.position.x = 4;
        this.transform.position.y = 4;
    }

    Update ()
    {
        this.nFramesSinceUpdate++;
        this.average += Time.deltaTime;
        if (this.nFramesSinceUpdate >= this.nFrameUpdateInterval)
        {
            this.text.text = "FPS: " + (1 / (this.average/ this.nFrameUpdateInterval)).toFixed(0);
            this.average = 0;
            this.nFramesSinceUpdate = 0;
        }
    }

    OnDestroy()
    {
        
    }

}


class ObjCounter extends MonoBehaviour
{

    nFrameUpdateInterval : number = 1;

    text : TextLabel;
    nFramesSinceUpdate : number = 0;

    Start()
    {
        this.text = this.gameObject.AddComponent(TextLabel);
        this.transform.position.x = 4;
        this.transform.position.y = 4.5;
    }

    Update ()
    {
        this.nFramesSinceUpdate++;
        if (this.nFramesSinceUpdate >= this.nFrameUpdateInterval)
        {
            this.text.text = "Objs " + SceneManager.GetActiveScene().objs.length;
            this.nFramesSinceUpdate = 0;
        }
    }

}
                    [Canvas] => //script:GUITest

class Canvas extends MonoBehaviour
{
  //Singleton pattern
  public static get instance (): Canvas
  {
    if (Canvas._instance == null || Canvas._instance == undefined)
    {
      Canvas._instance = Instantiate(Canvas);      
    }
    return Canvas._instance;
  }

  private static _instance: Canvas;  

  public static get main (): BABYLON.GUI.AdvancedDynamicTexture
  {
    return Canvas.instance.main;
  }

  private main : BABYLON.GUI.AdvancedDynamicTexture;

  Awake ()
  {
    this.main = BABYLON.GUI.AdvancedDynamicTexture.CreateFullscreenUI("Main", true, Engine.scene);
  }

  Update ()
  {

  }

  OnDestroy ()
  {
    this.main?.dispose();
    Canvas._instance = null;
  }
}
                    [TextLabel] => class TextLabel extends MonoBehaviour
{
  //MUST HAVE A DEFAULT VALUE OR MIGHT CRASH!
  public text : string = "";

  private label : BABYLON.GUI.TextBlock;

  Awake ()
  {
    this.label = new BABYLON.GUI.TextBlock();
    this.label.color = "white";
    this.label.fontSize = 30;
    this.label.outlineColor = "black";
    this.label.outlineWidth = 3;
    this.label.shadowColor = "black";
    this.label.shadowOffsetX = 2;
    this.label.shadowOffsetY = 2;

    Canvas.main.addControl(this.label);
    this.label.linkWithMesh(this.renderer.GetMesh());
  }

  Update ()
  {
    this.label.text = this.text;
    
  }
}
                    [ReflectionSystem] => //script:ReflectionSystem

class ReflectionSystem
{
  public static DefineParams (o : any)
  {
      //Looping through all keys (currently unused)
      Object.keys(o).forEach((name : string, i : number, array : string[]) => {
        
      });
      
      //Get all variables in object 
      var names = Object.getOwnPropertyNames(o);      

      //Loop through
      names.forEach((name : string) => {
        //Get Property based on name
        var p : PropertyDescriptor = Object.getOwnPropertyDescriptor(o, name);                
        
        //Check typeof property
        if (typeof p.value === "number")
        {
            //Set the value
            p.value = 0.01;
            //Apply change to actual object
            Object.defineProperty(o, name, p);
        }
      });

  }

  
}

                    [PlayerPrefs] => //script:Serializer

class PlayerPrefs
{
  public static Save (jsonData : string){
    
    Engine.current.getHostDocument().cookie = jsonData + "|||";
  }

  public static Load() : string
  {
    var cookie : string = Engine.current.getHostDocument().cookie;

    if (cookie.startsWith("{"))
    {
        cookie = cookie.split("|||")[0];
        return cookie;
    }
    return null;
  }

  public static Clear ()
  {
    this.Save("{}");
    
  }
}

                    [UnionEditor] => class UnionButton extends BABYLON.GUI.Button {

  get textBlock () : BABYLON.GUI.TextBlock
  {
    return this._tb;
  }

  private _tb;

  constructor(text: string) {
    super("Button_" + text);
    this.width = "100px";
    this.height = "20px";
    this.color = UnionEditor.style.darkLineColor.toHexString();
    this.background = UnionEditor.style.backgroundColor.toHexString();
    // Adding text
    this._tb = new BABYLON.GUI.TextBlock(name + "_button", text);
    this._tb.textWrapping = true;
    this._tb.textHorizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_CENTER;
    this._tb.paddingLeft = "10%";
    this._tb.paddingTop = "20%";
    this._tb.paddingBottom = "10%";
    this._tb.fontSize = 12;
    this._tb.color = UnionEditor.style.textColor.toHexString();
    
    this.addControl(this._tb);
  }
}

class UnionEditor {

  public static style: EditorStyle;

  public static editorUI: BABYLON.GUI.AdvancedDynamicTexture;

  public static editorCam: EditorCam;

  public static editorSystems: EditorSystem[];

  public static fontStyle: BABYLON.GUI.Style;
  public static fontStyleBold: BABYLON.GUI.Style;

  private static uiCam: BABYLON.TargetCamera;

  private static topBar: TopBarWindow;

  public static CreateButton(name: string): BABYLON.GUI.Button {
    var button = new UnionButton(name);
    return button;
  }

  public static SetTitle(name: string) {
    this.topBar.breadCrumbsText.textBlock.text = name;
  }

  public static Init() {
    this.style = new EditorStyle();

    this.editorSystems = new Array();

    //Here to render the User Interface
    this.uiCam = new BABYLON.TargetCamera("uiCam", new BABYLON.Vector3(999999, 0, 999999), Engine.editorScene);
    this.uiCam.viewport = new BABYLON.Viewport(0, 0, 1, 1);
    this.uiCam.attachControl(Engine.canvas, true);

    UnionEditor.editorCam = new EditorCam();

    Engine.editorScene.activeCameras.push(this.uiCam);




    new EditorSelection();
    new EditorGizmos();

    this.editorUI = BABYLON.GUI.AdvancedDynamicTexture.CreateFullscreenUI("UI", true, Engine.editorScene);
    this.editorUI.layer.layerMask = 1;

    this.fontStyle = new BABYLON.GUI.Style(this.editorUI);
    this.fontStyle.fontSize = 12;

    this.fontStyleBold = new BABYLON.GUI.Style(this.editorUI);
    this.fontStyleBold.fontSize = 13;
    this.fontStyleBold.fontWeight = "bold";

    this.topBar = new TopBarWindow();
    new HierarchyWindow();
    new InspectorWindow();

    Engine.scene.onKeyboardObservable.add((keybrd) => {

      if (keybrd.type == BABYLON.KeyboardEventTypes.KEYDOWN && keybrd.event.key == "d") {
        if (keybrd.event.ctrlKey) {
          keybrd.event.preventDefault();

          var sel = EditorSelection.GetSelection();

          /*
          //Prefabs only have a single root-object
          if (SceneManager.prefabLoaded && !c.transform.parent)
          {
            Debug.Log("Can't duplicate the root-object of a prefab!");
            return;
          }
          */
          EditorSelection.SetSelectedGameObject(null);
          sel.forEach(c => {
            var jsonData: string = Serializer.ToJSON(c);
            //Create a clone from data read from the string.
            var clone: GameObject = Serializer.FromJSON(jsonData) as GameObject;
            clone.transform.SetParent(c.transform.parent, false);
            EditorSelection.AddSelectedGameObject(clone);
          });

        }
      }
    });

  }

  public static Update() {

    if (Input.GetKeyDown(KeyCode.Delete)) {
      var sel = EditorSelection.GetSelection();
      sel.forEach(c => {
        Destroy(c);

        EditorSelection.SetSelectedGameObject(null);
      });
    }


    this.editorSystems.forEach((window: EditorWindow) => {
      window.OnGUI();
    });
  }

}

class EditorStyle {

  //Unity grey
  backgroundColor = new BABYLON.Color3(0.219607843, 0.219607843, 0.219607843);
  lineColor = new BABYLON.Color4(0, 0, 0, 0);
  darkLineColor = new BABYLON.Color3(0.1, 0.1, 0.1);
  textColor = new BABYLON.Color3(0.8, 0.8, 0.8);
  selectedTextColor = new BABYLON.Color3(1, 1, 1);
  selectionColor = new BABYLON.Color3(0.17254902, 0.364705882, 0.529411765);
  hoverColor = new BABYLON.Color3(0.270588235, 0.270588235, 0.270588235);
}


                    [EditorWindow] => class EditorWindow extends EditorSystem
{
  private sv : BABYLON.GUI.ScrollViewer;

  public main : BABYLON.GUI.StackPanel;

  public background : BABYLON.GUI.Rectangle;

  constructor()
  {
    super();    
    this.Refresh();
  }

  OnGUI()
  {

  }

  Refresh()
  {
    this.main?.dispose();
    this.sv?.dispose();

    if (this.background)
    {
      this.background.onPointerEnterObservable.clear();
      this.background.onPointerOutObservable.clear();
    }

    this.background?.dispose();

    this.main = new BABYLON.GUI.StackPanel();
    
    this.main.fontSize = 14;

    this.main.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.main.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;
    
    this.background = new BABYLON.GUI.Rectangle();
    this.background.cornerRadius = 0;
    this.background.background = UnionEditor.style.backgroundColor.toHexString();
    this.background.color = UnionEditor.style.darkLineColor.toHexString();

    this.background.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;
    this.background.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_RIGHT;
      
    this.sv = new BABYLON.GUI.ScrollViewer();
    this.sv.color = UnionEditor.style.darkLineColor.toHexString();
    this.background.addControl(this.sv);

    this.sv.addControl(this.main);
    this.sv.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.sv.barSize = 15;
    
    this.background.widthInPixels = 200;
    this.background.heightInPixels = 350;

    this.background.zIndex = -1;
    
    this.background.onPointerEnterObservable.add((evt) => {
      EditorUI.SetPointerOverEditor(this);
    });

    this.background.onPointerOutObservable.add((evt) => {
      EditorUI.SetPointerOverEditor(null);
    });
    
    UnionEditor.editorUI.addControl(this.background);
  }
}
                    [MenuButton] => class MenuButton extends UnionButton {

  data : MenuItemData;

  constructor(data: MenuItemData) {
    super(data.name);
    this.data = data;
    this.onPointerClickObservable.add(e => {
      if (data.functionToCall)
      {
        data.functionToCall();
      }
      if (data.children.length > 0)
      {
        var list = new MenuButtonList(data.children);
        print(this.transformCenterX)
        this.addControl(list);
      }
    });
  }
}








function MenuItem(value: string) {
  return function (target: any, propertyKey: string, descriptor: PropertyDescriptor) {
    {
      if (!MenuItemData.main) {
        MenuItemData.main = new MenuItemData();
      }
      MenuItemData.main.AddChild(value.split("/"), descriptor.value);
    }
  }
}

class MenuItemData {
  static main: MenuItemData;

  name: string;
  functionToCall: any;
  children: MenuItemData[] = new Array();

  constructor() {

  }

  AddChild(items: string[], functionToCall: any) {
    //Pop off the start of items
    var name = items.shift();

    var child = null;
    //If the child already exists, we group them under the same MenuItemData
    this.children.forEach(c => {
      if (c.name == name)
      {
        child = c;
      }
    });

    //If it didn't exist yet in the menu, we add it here
    if (!child)
    {
      child = new MenuItemData();
      this.children.push(child);
      child.name = name;
    }

    //If there are more submenus...
    if (items.length > 0) {
      child.AddChild(items, functionToCall);
    } else {
      child.functionToCall = functionToCall;
    }
  }
}
                    [MenuButtonList] => class MenuButtonList extends BABYLON.GUI.StackPanel {

  constructor(itemDataList: MenuItemData[], isVertical = true) {
    super();

    this.isVertical = isVertical;
    if (isVertical) {

      this.widthInPixels = 100;
    } else {
      this.heightInPixels = 20;
    }
    this.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;


    itemDataList.forEach(itemData => {
      var button = new MenuButton(itemData);

      this.addControl(button);
    });
  }
}

                    [TopBarWindow] => class TopBarWindow extends EditorWindow {

  public static items : string[];

  private topPanel: BABYLON.GUI.StackPanel;
  private breadCrumbs: BABYLON.GUI.StackPanel;
  public breadCrumbsText: BABYLON.GUI.Button;

  @MenuItem("GameObject")
  static DoTest(): string {
    print("Hello world");
    return null
  }


  @MenuItem("GameObjec3t/MyButto3n")
  static DoTest2(): string {
    print("Hello world");
    return null
  }

   @MenuItem("GameObjec3t/MyButtdoasd3n")
  static DoTest3(): string {
    print("Hello world");
    return null
  }

    @MenuItem("GameObjec3t/MyButasdasdtdo3n")
  static DoTest4(): string {
    print("Hello world");
    return null
  }

  constructor() {
    super()
    this.background.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;
    this.background.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.background.widthInPixels = 600;
    this.background.heightInPixels = 40;

    this.breadCrumbs = new BABYLON.GUI.StackPanel();

    this.breadCrumbs.isVertical = false;
    this.breadCrumbs.height = "20px";

    this.breadCrumbs.fontSize = "14px";
    this.breadCrumbs.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.breadCrumbs.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;

    this.breadCrumbsText = UnionEditor.CreateButton("Breadcrumbs");
    this.breadCrumbsText.widthInPixels = 200;
    this.breadCrumbs.addControl(this.breadCrumbsText);
    
    var buttons = new Array();

    
    this.background.addControl(this.breadCrumbs);
    var buttonList = new MenuButtonList(MenuItemData.main.children, false);
    this.background.addControl(buttonList);


    this.topPanel = new BABYLON.GUI.StackPanel();

    this.topPanel.isVertical = false;
    this.topPanel.height = "20px";

    this.topPanel.fontSize = "14px";
    this.topPanel.topInPixels = 20;
    this.topPanel.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.topPanel.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;
    //topPanel.isVertical = false;
    this.background.addControl(this.topPanel);

    var playButton = UnionEditor.CreateButton("Play");
    this.topPanel.addControl(playButton);
    playButton.onPointerClickObservable.add(() => {
      //The scene saves when going into PlayMode
      if (!Engine.isPlaying) {
        SceneManager.SaveCurrentScene();
      }

      if (!Engine.isPlaying) {
        Engine.SwitchToPlayMode();
      } else {
        Engine.StopPlayMode();
      }
      EditorSelection.SetSelectedGameObject(null);
      //Reloading the current scene, which should reset if not playing
      SceneManager.ReloadCurrentScene();

      playButton.textBlock.text = Engine.isSwitchingToPlayMode ? "Stop" : "Play";
    });

    var newObjectButton = UnionEditor.CreateButton("New Object");
    this.topPanel.addControl(newObjectButton);
    newObjectButton.onPointerClickObservable.add(() => {
      var go: GameObject = new GameObject();
      if (EditorSelection.HasSelection()) {
        go.transform.SetParent(EditorSelection.GetSelection()[0].transform, false);
      }
      EditorSelection.SetSelectedGameObject(go);
    });

    var newBoxButton = UnionEditor.CreateButton("New Box");
    this.topPanel.addControl(newBoxButton);
    newBoxButton.onPointerClickObservable.add(() => {
      var go: GameObject = new GameObject();
      go.name = "Box";
      go.AddComponent(Renderer).LoadMesh("box");
      go.AddComponent(BoxCollider);
      go.AddComponent(Rigidbody);
      if (EditorSelection.HasSelection()) {
        go.transform.SetParent(EditorSelection.GetSelection()[0].transform, false);
      }
      EditorSelection.SetSelectedGameObject(go);
    });

    var createParentButton = UnionEditor.CreateButton("Create Parent");
    this.topPanel.addControl(createParentButton);
    createParentButton.onPointerClickObservable.add(() => {
      var go: GameObject = new GameObject();
      go.transform.position = EditorSelection.GetCenterPoint();
      var first = true;
      EditorSelection.GetSelection().forEach(sgo => {
        if (first) {
          first = false;
          var i: number = sgo.transform.GetSiblingIndex();
          go.transform.SetParent(sgo.transform.parent, true);
          go.transform.SetSiblingIndex(i + 1);
        }
        sgo.transform.SetParent(go.transform, true);
      });
      EditorSelection.SetSelectedGameObject(go);
    });

    var modeViewerButton = UnionEditor.CreateButton("File Viewer");
    this.topPanel.addControl(modeViewerButton);
    modeViewerButton.onPointerClickObservable.add(() => {
      InspectorWindow.current.OnViewModel();
    });

  }

}

                    [HierarchyWindow] => class HierarchyWindow extends EditorWindow
{
  public static refresh : boolean = false;

  public static idToCollapseState: Record<number, boolean> 

  lastObjects : number = 0;

  private list : HierarchyList;


  constructor ()
  {
    super();
    HierarchyWindow.idToCollapseState = {};
    SceneManager.onSceneLoaded.add((ev: Scene) => {
      this.DisplaySceneHierarchy();
    });
  }


  OnGUI ()
  {
    super.OnGUI();
    var scene = SceneManager.GetActiveScene();
    //Simple 'isDirty' check
    if (scene != null && (scene.objs.length != this.lastObjects || HierarchyWindow.refresh))
    {
      HierarchyWindow.refresh = false;
      this.lastObjects = scene.objs.length;
      this.DisplaySceneHierarchy();
    }
  }

  DisplaySceneHierarchy ()
  {
    this.Refresh();
    this.list?.dispose();
    var scene = SceneManager.GetActiveScene();

    this.list = new HierarchyList(scene.rootObjs, 0, null);
    this.main.addControl(this.list);
  }
}
                    [HierarchyList] => class HierarchyList extends BABYLON.GUI.StackPanel {

  tabSize: number = 10;
  parentItem: HierarchyItem
  items: HierarchyItem[];

  constructor(objs: GameObject[], offset: number, parentItem: HierarchyItem) {
    super("HierarchyList");

    this.parentItem = parentItem;
    this.leftInPixels = offset;


    this.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;

    var i: number = 0;
    this.items = new Array();

    objs.forEach((obj: GameObject) => {
      if (!obj)
        return;


      var splitItem = new HierarchySplitItem(this, i);
      this.addControl(splitItem.background);
      i++;

      var item = new HierarchyItem(this, obj);
      this.items.push(item);
      this.addControl(item.p);
      
      item.onSelect.add(() => {
        if (Input.GetKey(KeyCode.Control)) {
          EditorSelection.ToggleSelectedGameObject(obj);

        } else if (Input.GetKey(KeyCode.Shift)) {
          if (EditorSelection.HasSelection()) {
            var sel = EditorSelection.GetSelection();
            var lastSelectedObject = sel[sel.length - 1];

            this.state = 0;
            this.GoThroughSelection(SceneManager.GetActiveScene().rootObjs, lastSelectedObject, item.obj);
          }
        } else {
          EditorSelection.SetSelectedGameObject(obj);
        }


      });

      //Slighly hacky way to ensure the newly created object is selected.
      /*
      if (obj == EditorSelection.currentSelectedGameObject) {
        EditorSelection.SetSelectedGameObject(null);
        EditorSelection.SetSelectedGameObject(obj);
      }
      */
    });

    //One split item at the bottom
    var splitItem = new HierarchySplitItem(this, i);
    this.addControl(splitItem.background);
  }

  state: number = 0;

  GoThroughSelection(siblings: GameObject[], go1: GameObject, go2: GameObject) {

    //state starts at 0: which means don't select items
    //when it hits either GameObject, start selecting
    //when it hits the next GameObject, stop everything.

    //We collapse all children before we collapse a parent
    siblings.forEach(item => {
      if (this.state > 1)
        return;

      if (item === go1 || item === go2) {
        EditorSelection.AddSelectedGameObject(item);
        this.state++;
      }

      if (this.state == 1) {
        EditorSelection.AddSelectedGameObject(item);
      }

      this.GoThroughSelection(item.transform.ser_children, go1, go2);



    });
  }
}


                    [HierarchyItem] => 
class BaseHierarchyItem {

    background: BABYLON.GUI.Rectangle;
    parentList: HierarchyList;

    constructor (parentList: HierarchyList)
    {
        this.parentList = parentList;
        this.background = new BABYLON.GUI.Rectangle("Block");
        this.background.color = UnionEditor.style.backgroundColor.toHexString();
        this.background.widthInPixels = 200;
        //Needs to block the pointer for the Gizmos to work.
        this.background.isPointerBlocker = true;
    }

    OnReleased ()
    {
        
    }
}


class HierarchyItem extends BaseHierarchyItem {

    public static justClicked: HierarchyItem = null;

    public onChange: BABYLON.Observable<string>;
    public onSelect: BABYLON.Observable<boolean>;

    //Tweakables
    itemHeight: number = 20;

    maxDoubleClickDelay: number = 0.3;

    //Gameplay Vars
    public static currentDraggedItem: HierarchyItem = null;
    public static currentHoveredItem: BaseHierarchyItem = null;

    lastClickTime: number;
    isSelected: boolean;
    obj: GameObject;
    isCollapsed = true;

    //References
    public p: BABYLON.GUI.StackPanel;
    private collapseButton: BABYLON.GUI.Button;
    childrenList: HierarchyList;

    private nameField : BABYLON.GUI.TextBlock;

    constructor(parentList: HierarchyList, obj: GameObject) {
        super(parentList);
        
        this.obj = obj;

        

        this.onChange = new BABYLON.Observable();
        this.onSelect = new BABYLON.Observable();

        this.p = new BABYLON.GUI.StackPanel();
        this.p.width = "200px";
        this.p.heightInPixels = this.itemHeight;
        this.p.isVertical = true;
        this.p.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT


        var item = new BABYLON.GUI.StackPanel();;
        item.width = "200px";
        item.height = "20px";
        item.isVertical = false;
        this.p.addControl(item);  

        if (obj.transform.ser_children.length > 0) {
            this.collapseButton = BABYLON.GUI.Button.CreateSimpleButton("B", ">");
            this.collapseButton.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
            this.collapseButton.width = "20px";
            this.collapseButton.color = UnionEditor.style.lineColor.toHexString();
            this.collapseButton.textBlock.color = UnionEditor.style.textColor.toHexString();
            this.background.addControl(this.collapseButton);

            this.collapseButton.onPointerUpObservable.add((evt) => {
                this.SetCollapse(!this.isCollapsed);
            });
        }

        this.nameField = new BABYLON.GUI.TextBlock("", obj.name);
        this.nameField.paddingLeft = "25px";
        this.nameField.paddingTopInPixels = 3;
        this.nameField.color = obj.prefabParent ? UnionEditor.style.selectionColor.toHexString() : UnionEditor.style.textColor.toHexString();
        this.nameField.textHorizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
        this.background.addControl(this.nameField);


        var nameInput: BABYLON.GUI.InputText = null;

        this.background.onPointerDownObservable.add((ev) => {
            HierarchyItem.currentDraggedItem = this;
            HierarchyItem.currentHoveredItem = this;
        });

        //This pointerUp is called when releasing a the pointer 
        //but NOT on the object the poitner is released, but rather where it was released from
        this.background.onPointerUpObservable.add((ev) => {
            var hover = HierarchyItem.currentHoveredItem;
            
            if (!hover) {
               
               //If not hovering over itself
            } else if (this != hover) {
                hover.OnReleased();                
                HierarchyWindow.refresh = true;
            }

            HierarchyItem.currentDraggedItem = null;
        });

        Engine.scene.onPointerObservable.add((pointerInfo) => {
            if (pointerInfo.type == BABYLON.PointerEventTypes.POINTERUP) {
                if (this != HierarchyItem.currentDraggedItem)
                    return;
            }
        });

        this.background.onPointerEnterObservable.add((ev) => {
            if (HierarchyItem.currentDraggedItem) {

                if (HierarchyItem.currentDraggedItem != this) {
                    HierarchyItem.currentHoveredItem = this;
                    this.background.background = UnionEditor.style.hoverColor.toHexString();
                }
            }
        });

        this.background.onPointerOutObservable.add((ev) => {
            if (HierarchyItem.currentHoveredItem == this) {
                HierarchyItem.currentHoveredItem = null;
                if (!EditorSelection.IsSelected(this.obj)) {
                    this.background.background = UnionEditor.style.backgroundColor.toHexString();
                }
            }
        });


        this.background.onPointerClickObservable.add((ev) => {
            HierarchyItem.justClicked = this;
            var clickDelay = Time.time - this.lastClickTime;
            //Double click
            if (clickDelay < this.maxDoubleClickDelay) {

                if (nameInput == null) {
                    nameInput = new BABYLON.GUI.InputText();
                    nameInput.text = this.nameField.text;
                    nameInput.width = 1;
                    nameInput.height = 1;
                    nameInput.color = UnionEditor.style.selectedTextColor.toHexString();
                    nameInput.textHighlightColor = UnionEditor.style.selectionColor.toHexString();
                    nameInput.onFocusSelectAll = true;
                    this.background.addControl(nameInput);
                    UnionEditor.editorUI.moveFocusToControl(nameInput);
                    nameInput.onBlurObservable.add((ev) => {
                        this.nameField.text = nameInput.text;
                        nameInput.dispose();
                        nameInput = null;
                        obj.name = this.nameField.text;
                        this.onChange.notifyObservers(obj.name);
                    });
                }
                //Single click
            } else {
                this.onSelect.notifyObservers(this.isSelected);
            }

            this.lastClickTime = Time.time;

        });

        item.addControl(this.background);
        item.paddingBottomInPixels = -3;
        item.paddingTopInPixels = -3;
        EditorSelection.onSelected.add((data: SelectionData) => {
            if (data.obj === obj) {
                this.SetSelected(data.isSelected);
            }
        });

        var collapsed = HierarchyWindow.idToCollapseState[obj.transform.uniqueId];
        this.SetCollapse(collapsed);
        this.SetSelected(EditorSelection.IsSelected(obj));
    }


    SetCollapse(value: boolean) {
        //Can't collapse if it has no children
        if (value == this.isCollapsed || this.obj.transform.ser_children.length == 0)
            return;

        
        this.isCollapsed = value;
        HierarchyWindow.idToCollapseState[this.obj.transform.uniqueId] = value;
        this.collapseButton.textBlock.text = this.isCollapsed ? ">" : "v";
        if (this.isCollapsed) {
            //We collapse all children before we collapse a parent
            this.childrenList.items.forEach(item => {
                item.SetCollapse(true);
            });
            this.childrenList.dispose();
            this.AddToHeight(-this.obj.transform.ser_children.length);
        } else {
            //TODO Clean this up! The HierarchyWindow shouldn't clean the child list
            this.obj.transform.ser_children = this.obj.transform.ser_children.filter(function (el) {
                return el != null;
            });
            
            this.childrenList = new HierarchyList(this.obj.transform.ser_children, this.parentList.tabSize, this);
            this.p.addControl(this.childrenList);
            this.AddToHeight(this.obj.transform.ser_children.length);
        }
    }

    public AddToHeight(nElements: number) {
        this.p.heightInPixels += nElements * (this.itemHeight + 5); //5 because of HierarchySplitItemWidth <- FIX THIS!
        this.p.heightInPixels += nElements > 0 ? 5 : -5;
        if (this.parentList.parentItem) {
            this.parentList.parentItem.AddToHeight(nElements);
        }
    }

    OnReleased ()
    {
        //After releasing, parent all selected objects to here.
        EditorSelection.GetSelection().forEach(go =>
        {
            go.transform.SetParent( this.obj.transform, true);
        });
    }

    SetSelected (value : boolean)
    {
        if (value) {
            this.background.background = UnionEditor.style.selectionColor.toHexString();
            this.nameField.color = UnionEditor.style.textColor.toHexString();
        } else {
            this.background.background = UnionEditor.style.backgroundColor.toHexString();
            this.nameField.color = this.obj.prefabParent ? UnionEditor.style.selectionColor.toHexString() : UnionEditor.style.textColor.toHexString();
        }
    }
}

class HierarchySplitItem extends BaseHierarchyItem {

    siblingIndex : number;

    constructor(parentList : HierarchyList, siblingIndex : number) {
        super(parentList);

        this.siblingIndex = siblingIndex;

        this.background.heightInPixels = 5;
        this.background.color = UnionEditor.style.backgroundColor.toHexString();

        
        this.background.onPointerEnterObservable.add((ev) => {
            if (HierarchyItem.currentDraggedItem) {
                HierarchyItem.currentHoveredItem = this;
                this.background.background = UnionEditor.style.selectionColor.toHexString();                
            }
        });

        this.background.onPointerOutObservable.add((ev) => {
            if (HierarchyItem.currentHoveredItem == this) {
                HierarchyItem.currentHoveredItem = null;
                this.background.background = UnionEditor.style.backgroundColor.toHexString();
            }
        });
    }

    //The SplitItem returns it's parent since that will be the new parent for a dragged on object
    OnReleased ()
    {
        //After releasing, parent all objects to my parent and set the sibling index
        //TODO The splitter item should also know the parent
        EditorSelection.GetSelection().forEach(go => 
        {
            var i = go.transform.GetSiblingIndex();
            var parentChange = go.transform.parent != this.parentList?.parentItem?.obj.transform;

            go.transform.SetParent(this.parentList?.parentItem?.obj.transform, true);
            
            //Only if the parent didn't change, and we moved it from a higher spot to a lower spot
            //We have to adjust our sibling index
            if (parentChange || i > this.siblingIndex)
            {
                this.siblingIndex += 1;
            }
            
            go.transform.SetSiblingIndex(this.siblingIndex);
        });
    }
}


                    [EditorGizmos] => class EditorGizmos extends EditorSystem {
  gizmoManager: BABYLON.GizmoManager;

  private gizmoMode: Mode;

  private prevMode: Mode;

  //Since the GizmoManager requires a mesh to attach the pivot to, we will just use a dummy Mesh
  private dummyMesh: BABYLON.Mesh;

  private canSelect: boolean;

  private isMoving: boolean;
  private isRotating: boolean;
  private isScaling: boolean;

  private lastPos: BABYLON.Vector3 = new BABYLON.Vector3();
  private lastRot: BABYLON.Vector3 = new BABYLON.Vector3();

  constructor() {
    super()

    this.dummyMesh = new BABYLON.Mesh("GizmoDummyMesh", Engine.scene);

    // Initialize GizmoManager
    this.gizmoManager = new BABYLON.GizmoManager(Engine.scene);

    this.gizmoManager.keepDepthUtilityLayer.setRenderCamera(UnionEditor.editorCam.cam);
    this.gizmoManager.utilityLayer.setRenderCamera(UnionEditor.editorCam.cam);
    this.gizmoManager.attachToMesh(this.dummyMesh);
    this.gizmoManager.usePointerToAttachGizmos = false;

    //Need to enable once to access gizmos
    this.gizmoManager.positionGizmoEnabled = true;
    this.gizmoManager.rotationGizmoEnabled = true;
    this.gizmoManager.scaleGizmoEnabled = true;

    this.gizmoManager.gizmos.scaleGizmo.sensitivity = 10;

    this.gizmoManager.gizmos.positionGizmo.onDragStartObservable.add((ev) => {
      this.lastPos = this.dummyMesh.position.clone();
      this.isMoving = true;
    });
    this.gizmoManager.gizmos.positionGizmo.onDragEndObservable.add((ev) => {
      this.isMoving = false;
    });

    this.gizmoManager.gizmos.rotationGizmo.onDragStartObservable.add((ev) => {
      this.lastRot = this.dummyMesh.rotationQuaternion.toEulerAngles().clone();
      this.isRotating = true;
    });
    this.gizmoManager.gizmos.rotationGizmo.onDragEndObservable.add((ev) => {
      this.isRotating = false;
    });

    this.gizmoManager.gizmos.scaleGizmo.onDragStartObservable.add((ev) => {
      this.lastPos = this.dummyMesh.scaling.clone();
      this.isScaling = true;
    });
    this.gizmoManager.gizmos.scaleGizmo.onDragEndObservable.add((ev) => {
      this.isScaling = false;
    });


    this.gizmoManager.positionGizmoEnabled = false;
    this.gizmoManager.rotationGizmoEnabled = false;
    this.gizmoManager.scaleGizmoEnabled = false;

    this.gizmoMode = Mode.Translate;

    Engine.scene.onPointerObservable.add((pointerInfo) => {
      switch (pointerInfo.type) {
        case BABYLON.PointerEventTypes.POINTERDOWN:
          if (this.canSelect && !EditorUI.IsPointerOverEditor()) {
            var pickResult = Engine.scene.pick(Engine.scene.pointerX, Engine.scene.pointerY);
            var go: GameObject = null;
            if (pickResult.hit) {
              var mesh = pickResult.pickedMesh;
              go = !mesh?.parent ? null : Engine.meshToObject[mesh.parent.uniqueId];
            }
            if (Input.GetKey(KeyCode.Control)) {
              EditorSelection.ToggleSelectedGameObject(go);
            } else {
              if (go) {
                EditorSelection.SetSelectedGameObject(go);

              } else {
                EditorSelection.SetSelectedGameObject(null);
              }
            }

          }
          break;
        case BABYLON.PointerEventTypes.POINTERMOVE:

          break;
      }
    });


  }

  OnGUI() {
    var selection = EditorSelection.GetSelection();

    var hasSelection = selection?.length > 0;
    if (hasSelection) {

      if (this.isMoving) {
        //How much the gizmo moved
        var offset = this.dummyMesh.position.subtract(this.lastPos);

        selection.forEach(go => {
          //For some reason I need to set the entire Vector. The change is not recognized otherwise? 0_o
          go.transform.position = new Vector3(
            go.transform.position.x + offset.x,
            go.transform.position.y + offset.y,
            go.transform.position.z + offset.z
          );
        });

        this.lastPos = this.dummyMesh.position.clone();
      }
      else {
        Vector3.VtoB(EditorSelection.GetCenterPoint(), this.dummyMesh.position);
      }
      if (this.isRotating) {
        var offset2 = this.dummyMesh.rotationQuaternion.toEulerAngles().subtract(this.lastRot)

        selection.forEach(go => {
          //We set each individual value so the inspector displays it properly
          go.transform.localEulerAngles.x = go.transform.localEulerAngles.x + offset2.x * Mathf.Rad2Deg;
          go.transform.localEulerAngles.y = go.transform.localEulerAngles.y + offset2.y * Mathf.Rad2Deg;
          go.transform.localEulerAngles.z = go.transform.localEulerAngles.z + offset2.z * Mathf.Rad2Deg;


        });

        this.lastRot = this.dummyMesh.rotationQuaternion.toEulerAngles().clone();
      } else {
        //Just use the rotation of the first selected object...
        this.dummyMesh.rotationQuaternion = EditorSelection.GetSelection()[0].transform.transformNode.absoluteRotationQuaternion.clone();
      }

      if (this.isScaling) {
        var offset3 = this.dummyMesh.scaling.subtract(this.lastPos)

        selection.forEach(go => {

          go.transform.localScale.x = go.transform.localScale.x + offset3.x;
          go.transform.localScale.y = go.transform.localScale.y + offset3.y;
          go.transform.localScale.z = go.transform.localScale.z + offset3.z;

        });

        this.lastPos = this.dummyMesh.scaling.clone();
      }
    }


    this.gizmoManager.positionGizmoEnabled = this.gizmoMode == Mode.Translate && hasSelection;
    this.gizmoManager.rotationGizmoEnabled = this.gizmoMode == Mode.Rotate && hasSelection;
    this.gizmoManager.scaleGizmoEnabled = this.gizmoMode == Mode.Scale && hasSelection;
    this.gizmoManager.boundingBoxGizmoEnabled = false;

    if (Input.GetKeyDown(KeyCode.W)) {
      this.gizmoMode = Mode.Translate;
    }

    if (Input.GetKeyDown(KeyCode.E)) {
      this.gizmoMode = Mode.Rotate;
    }

    if (Input.GetKeyDown(KeyCode.R)) {
      this.gizmoMode = Mode.Scale;
    }

    if (Input.GetKey(KeyCode.Alt) || EditorUI.IsPointerOverEditor()) {
      this.canSelect = false;
      this.gizmoManager.clearGizmoOnEmptyPointerEvent = false;
      //this.gizmoManager.usePointerToAttachGizmos = false;
    } else {
      this.canSelect = true;
      this.gizmoManager.clearGizmoOnEmptyPointerEvent = true;
      //this.gizmoManager.usePointerToAttachGizmos = true;

    }

    if (Input.GetKeyDown(KeyCode.Escape)) {
      EditorSelection.SetSelectedGameObject(null);
    }

  }

}

enum Mode {
  Translate,
  Rotate,
  Scale
}
                    [EditorSelection] => class EditorSelection extends EditorSystem {
  constructor() {
    super();
    EditorSelection.onSelected = new BABYLON.Observable();
  }

  private static selected: GameObject[] = new Array();

  public static onSelected: BABYLON.Observable<SelectionData>


  public static IsSelected(value: GameObject, includeParent: boolean = false): boolean {
    return this.selected.indexOf(value) != -1;
  }

  public static HasSelection (): boolean{
    return this.GetSelection().length > 0;
  }

  public static currentSelectedGameObject;

  public static GetSelection(): GameObject[] {
    return this.selected;
  }

  public static GetCenterPoint(): Vector3 {
    var v = new Vector3();
    this.selected.forEach((s: GameObject) => {
      v.x += s.transform.position.x;
      v.y += s.transform.position.y;
      v.z += s.transform.position.z;
    });

    v.x /= this.selected.length;
    v.y /= this.selected.length;
    v.z /= this.selected.length;
    return v;
  }

  public static ToggleSelectedGameObject(go: GameObject) {
    if (EditorSelection.IsSelected(go)) {
      EditorSelection.RemoveSelectedGameObject(go);
    } else {
      EditorSelection.AddSelectedGameObject(go);
    }
  }

  public static AddSelectedGameObject(params: GameObject | GameObject[]) {
    if (!params)
      return;

    if (params instanceof GameObject)
      params = new Array(params);

    params.forEach(value => {
      if (this.selected.indexOf(value) == -1)
      {
        this.selected.push(value);
        this.onSelected.notifyObservers({ obj: value, isSelected: true });
      }
    });
  }

  public static RemoveSelectedGameObject(params: GameObject | GameObject[]) {
    if (!params)
      return;

    if (params instanceof GameObject)
      params = new Array(params);

    params.forEach(value => {
      const index = this.selected.indexOf(value);
      if (index > -1) {
        this.selected.splice(index, 1);
        this.onSelected.notifyObservers({ obj: value, isSelected: false });
      }
    });
  }

  private static ClearSelection() {
    //Clear old selection
    this.selected.forEach((s: GameObject) => {
      this.onSelected.notifyObservers({ obj: s, isSelected: false });
    });

    this.selected = new Array();
  }

  public static SetSelectedGameObject(params: GameObject | GameObject[]) {
    this.ClearSelection();

    this.AddSelectedGameObject(params);
  }
}

class SelectionData {
  obj: BaseObject;
  isSelected: boolean;
}
                    [InspectorWindow] => class InspectorWindow extends EditorWindow
{
  static current : InspectorWindow;
  currentGameObject : GameObject;

  constructor ()
  {
    super()
    InspectorWindow.current = this;
    EditorSelection.onSelected.add((data : SelectionData) => {
      
      if (data.isSelected)
      {
        this.RefreshGOInspector(data.obj as GameObject);
      } else {
        this.Refresh();
      }
    });

    PixelPADEvents.onMaterialClicked.add((matName: string) => {
      EditorSelection.SetSelectedGameObject(null);
      this.Refresh();
      this.main.addControl(new MaterialInspector(matName));
    });

    PixelPADEvents.onPrefabClicked.add((name: string) => {
      this.Refresh();
      this.main.addControl(new PrefabInspector(name));
    });
  }

  OnViewModel () 
  {
    this.Refresh();
    this.main.addControl(new ModelViewer());
  }

  OnGUI ()
  {
    //When a GO is about to be deleted, we should clear the inspector
    if (this.currentGameObject && this.currentGameObject.markedForDestroy)
    {
      this.Refresh();
    }
  }

  RefreshGOInspector (go : GameObject)
  {
    this.Refresh();
    this.currentGameObject= go;

    go.components.forEach((c : Component) => {
      if (!c.markedForDestroy)
      {
        this.main.addControl(new ComponentInspector(this, c));
      }
    });

    var addButton = BABYLON.GUI.Button.CreateSimpleButton("", "Add Component");
    addButton.width = "150px";
    addButton.height = "20px";
    addButton.color = UnionEditor.style.darkLineColor.toHexString();
    addButton.textBlock.color = UnionEditor.style.textColor.toHexString();
    this.main.addControl(addButton);
    addButton.onPointerClickObservable.add(() => {
      var dropDown = new Dropdown(3);
      dropDown.isSearchable = true;
      
      dropDown.SetOptions(this.GetAvailableComponents());
      this.main.addControl(dropDown);
      dropDown.onSelect.add((selected: string) =>
      {
          go.AddComponent(selected);
          this.RefreshGOInspector(go);
      });          
    });
    
    //Can't edit prefabs directly.
    if (go.prefabParent && go.prefabParent != go)
    {
      let coverPanel = new BABYLON.GUI.Rectangle();
      coverPanel.widthInPixels = this.background.widthInPixels;
      coverPanel.heightInPixels = this.background.heightInPixels;
      coverPanel.background = UnionEditor.style.backgroundColor.toHexString() + "88";
      this.background.addControl(coverPanel);
    }
  }

  //TODO reconsider the hackiness of this.
  //Can be done through Serializable Fields
  GetAvailableComponents () : string []
  {
    var components : string[] = new Array();
    var scripts : string[] = Engine.scripts;
    scripts.forEach((script : string) => {
      try {
        if (eval(script + ".isc") == true)
        {
          components.push(script);
        }
      } catch (e)
      {

      }
    });
    return components;
  }


  Refresh()
  {
    this.currentGameObject = null;
    super.Refresh();
    this.background.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_BOTTOM;
  }

}
                    [ComponentInspector] => //script:ComponentInspector

class ComponentInspector extends BABYLON.GUI.StackPanel {

  propertiesPanel: BABYLON.GUI.StackPanel;

  constructor(parent: InspectorWindow, c: Component) {
    super()

    var namePanel = new BABYLON.GUI.StackPanel();
    namePanel.heightInPixels = 20;
    namePanel.isVertical = false;
    this.addControl(namePanel);

    var collapseButton = BABYLON.GUI.Button.CreateSimpleButton("B", ">");
    collapseButton.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    collapseButton.width = "20px";
    collapseButton.color = UnionEditor.style.backgroundColor.toHexString();
    collapseButton.textBlock.color = UnionEditor.style.textColor.toHexString();
    namePanel.addControl(collapseButton);

    collapseButton.onPointerUpObservable.add((evt) => {
      this.propertiesPanel.isVisible = !this.propertiesPanel.isVisible;
      collapseButton.textBlock.text = this.propertiesPanel.isVisible ? "v" : ">";
    });


    var nameField = new BABYLON.GUI.TextBlock("", c.constructor.name);

    nameField.paddingLeft = "5px";
    nameField.style = UnionEditor.fontStyleBold;
    nameField.color = UnionEditor.style.textColor.toHexString();
    nameField.width = "140px";
    nameField.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;

    nameField.textHorizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    namePanel.addControl(nameField);
    namePanel.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;

    this.propertiesPanel = new BABYLON.GUI.StackPanel();
    this.propertiesPanel.isVisible = true;
    this.addControl(this.propertiesPanel);

    Serializer.GetPropValues(c, false).forEach((value: any, key: string) => {
      var propInspector: PropertyInspector = PropertyInspector.Create(c, key, value);

      this.propertiesPanel.addControl(propInspector);
    });


    if (c.GetType() == "Transform") {
      //TODO DRY this up
      this.propertiesPanel.isVisible = true;

    } else {
      //Transforms can't be removed
      var deleteButton = BABYLON.GUI.Button.CreateSimpleButton("deleteButton", "x");
      deleteButton.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_RIGHT;
      deleteButton.width = "20px";
      deleteButton.color = UnionEditor.style.darkLineColor.toHexString();
      deleteButton.textBlock.color = UnionEditor.style.textColor.toHexString();
      namePanel.addControl(deleteButton);

      deleteButton.onPointerUpObservable.add((evt) => {
        var go: GameObject = c.gameObject;
        Destroy(c);
        parent.RefreshGOInspector(go);
        /*
        var dropDown = new Dropdown();
          dropDown.AddOption("Remove Component");
          dropDown.linkOffsetXInPixels = 50;
          UnionEditor.editorUI.addControl(dropDown);
          dropDown.onSelect.add((selected: string) =>
          {
            if (selected == "Remove Component")
            {
              
            }
            
          });       
          */
      });

    }

    collapseButton.textBlock.text = this.propertiesPanel.isVisible ? "v" : ">";

  }
}
                    [PropertyInspector] => class PropertyInspector extends BABYLON.GUI.StackPanel {

  public onValueChanged : BABYLON.Observable<any> = new BABYLON.Observable();

  //This is a variable used to store which PropertyInspector was last created
  //We can use that to tab through the Inspectors after
  //Currently only setup in the Text Inspector
  public static lastCreated : PropertyInspector;

  protected previous : PropertyInspector;
  public next : PropertyInspector; 

  isAdjustingByMouse: boolean = false;
  isOverThisControl : boolean = false;

  //The object that 'owns' this property
  owner : any;
  propName : string;
  valueInput : any;

  get value () : any
  {
    return this._value;
  }

  set value (v : any)
  {
    this._value = v;
  }

  _value : any;

  constructor(owner: any, propName: string, value: any) {
    super()

    this.isVertical = false;
    //this.adaptWidthToChildren = true;
    this.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.owner = owner;
    this.propName = propName;
    this.value = value;

    this.heightInPixels = 20;
    var nameField = new BABYLON.GUI.TextBlock("", this.ConvertToInspectorName(propName));
    nameField.paddingLeft = "5px";
    nameField.color =  UnionEditor.style.textColor.toHexString();
    nameField.style = UnionEditor.fontStyle;
    if (owner instanceof Component)
    {
      nameField.width = "60px"
    }
    else
    {
      nameField.resizeToFit = true;
    }
    nameField.textHorizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.addControl(nameField); 
  }

  ConvertToInspectorName(value : string)
  {
    value = value.replace("_", " ");
    value = value.trim();
    //Start with uppercase
    value = value[0].toUpperCase() + value.substring(1, value.length);
    return value;
  }

  Init ()
  {

  }

  UpdateProperty ()
  {    
    this.owner[this.propName] = this.value;
    this.onValueChanged.notifyObservers(this.value);
  }

  public static Create(c : any, key : string, value : any): PropertyInspector
  {
    var propInspector : PropertyInspector =null;
    //TODO using declarations in the Inspector classes this could be a bit prettier.      
    if (value === null) {
      return;
    } else if (typeof value === 'number') {
      propInspector = new NumberInspector(c, key, value);
    } else if (typeof value === 'string') {
      propInspector = new TextInspector(c, key, value);
    } else if (typeof value === 'boolean') {
      propInspector = new BoolInspector(c, key, value);
    } else if (value instanceof Array) {
      //TODO Add ArrayInspector
      propInspector = new ArrayInspector(c, key, value);
    } else if (value instanceof Vector3) {
      propInspector = new Vector3Inspector(c, key, value);
    } else if (value instanceof Color) {
      propInspector = new ColorInspector(c, key, value);
    }  else {
      propInspector = new TextInspector(c, key, value);
    }
    propInspector.Init();
    return propInspector;
  }

}
                    [EditorUI] => //TODO is this Editor only?
class EditorUI
{
  //A variety of objects (such as EditorWindows, but also ColorPicker) use this
  private static obj : any

  public static SetPointerOverEditor(window : any)
  {
    this.obj = window;
  }

  public static IsPointerOverEditor () : boolean
  {
    return this.obj != null;
  }

}
                    [TextInspector] => class TextInspector extends PropertyInspector
{
  Init()
  {
    super.Init();

    if (PropertyInspector.lastCreated)
    {
      //We set this up to tab through this afterwards
      this.previous = PropertyInspector.lastCreated;
      PropertyInspector.lastCreated.next = this;
    }
    PropertyInspector.lastCreated = this;

    this.valueInput = new BABYLON.GUI.InputText();
    var vInput : BABYLON.GUI.InputText = this.valueInput;
    
    this.valueInput.style = UnionEditor.fontStyle;
    this.valueInput.paddingLeftInPixels = 2;
    this.valueInput.paddingRightInPixels = 2;
    this.valueInput.margin = "2px";
    this.valueInput.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    var x = new BABYLON.GUI.InputText();
    this.valueInput.text = this.value ? this.value : "";
    this.valueInput.width = "100px";
    this.valueInput.color =  UnionEditor.style.textColor.toHexString();
    
    this.valueInput.onFocusSelectAll = true;
    //When inspector inputfield is changed, update value
    this.valueInput.onBlurObservable.add((ev) => {
      this.UpdateValueFromText();
      this.UpdateProperty ();
    });

    vInput.onKeyboardEventProcessedObservable.add((event) => {
      if (event.key == "Tab")
      {
        var nextSelected : PropertyInspector = this.next;
        if (event.shiftKey)
        {
          nextSelected = this.previous;
        }
        if (nextSelected)
        {
          UnionEditor.editorUI.moveFocusToControl(nextSelected.valueInput);
          event.preventDefault();
        }
      }
    });    

    this.addControl(this.valueInput);
    //Update the value every frame in the inspector
    Engine.scene.onBeforeRenderObservable.add(() =>
    {
       this.UpdateTextFromValue ();
    });
  }

  UpdateValueFromText()
  {
    if (this.value != this.valueInput.text)
    {
      this.value = this.valueInput.text;
      SceneManager.MarkSceneAsDirty();
    }
  }

  UpdateTextFromValue ()
  {  
    if (this.value != this.owner[this.propName])
    {
      this.value = this.owner[this.propName];
      this.valueInput.text = this.value;
      SceneManager.MarkSceneAsDirty();
    }
  }
}
                    [NumberInspector] => //script:NumberInspector
class NumberInspector extends TextInspector {
  
  mouseAdjustSensitivity : number = 0.01;
  
  public Init() {
    super.Init();
    if (this.valueInput.text == "")
    {
      this.valueInput.text = "0";
    }
    this.valueInput.width = "30px";
   
    this.onPointerEnterObservable.add((ev) => {
      Engine.cursor = "ew-resize";
      this.isOverThisControl = true;
    });
    this.onPointerOutObservable.add((ev) => {
      Engine.cursor = "default";
      this.isOverThisControl = false;
    });

    Engine.scene.onPointerObservable.add((pointerInfo) => {
      switch (pointerInfo.type) {
        case BABYLON.PointerEventTypes.POINTERDOWN:
          if (this.isOverThisControl) {
            this.isAdjustingByMouse = true;
            Engine.current.enterPointerlock();
          }
          break;
        case BABYLON.PointerEventTypes.POINTERUP:
          this.isAdjustingByMouse = false;
          Engine.current.exitPointerlock();
          break;
        case BABYLON.PointerEventTypes.POINTERMOVE:
          if (this.isAdjustingByMouse) {
            this.value += pointerInfo.event.movementX * this.mouseAdjustSensitivity;
            this.valueInput.text = this.value;
            this.UpdateProperty();
          }
          break;
      }
    });
  }

  UpdateTextFromValue()
  {
    super.UpdateTextFromValue();
  }


  //Convert the text into a number
  
  UpdateValueFromText() {
    if (this.value != +this.valueInput.text)
    {
      var x = +this.valueInput.text;
      if (Number.isNaN(x))
      {
        this.valueInput.text = "0";
        x = 0;
      }

      this.value = x;
      
      SceneManager.MarkSceneAsDirty();
    }
  }
  
}
                    [ArrayInspector] => class ArrayInspector extends PropertyInspector {

  Init()
  {
    super.Init();
  }
}

                    [ColorInspector] => class ColorInspector extends PropertyInspector {

currentPicker : BABYLON.GUI.ColorPicker;

Init()
  {
    super.Init();

    var colorButton : BABYLON.GUI.Button = BABYLON.GUI.Button.CreateSimpleButton("B", "");
    
    colorButton.paddingLeftInPixels = 2;
    colorButton.paddingRightInPixels = 2;
    colorButton.width = "15px";
    colorButton.height = "10px";
    colorButton.color = "white";

    var c : BABYLON.Color3 = new BABYLON.Color3();
    Color.UtoB(this.value, c);
    
    colorButton.background = c.toHexString();    
    colorButton.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;

    this.addControl(colorButton);

    colorButton.onPointerClickObservable.add((ev) => {  
      this.TryDisposeColorPicker();
      this.currentPicker = new BABYLON.GUI.ColorPicker("Color Picker");
      this.currentPicker.value = c;
      this.currentPicker.onValueChangedObservable.add(() => {
        Color.BtoU(this.currentPicker.value, this.value);
        this.UpdateProperty();
        colorButton.background = this.currentPicker.value.toHexString();        
      });
      this.currentPicker.onPointerEnterObservable.add((ev) => {
        EditorUI.SetPointerOverEditor(this.currentPicker);
      })
      this.currentPicker.onPointerOutObservable.add((ev) => {
        EditorUI.SetPointerOverEditor(null);
      })
      UnionEditor.editorUI.addControl(this.currentPicker);
    });

    Engine.editorScene.onPointerObservable.add((pointerInfo) => {
      if (pointerInfo.type === BABYLON.PointerEventTypes.POINTERUP) {        
        this.TryDisposeColorPicker();
      }
    });

    

    this.onDisposeObservable.add(() => {
      this.TryDisposeColorPicker();
    });
    /*
    this.valueInput.color = this.value;
    this.valueInput.onValueChangedObservable.add(() => {
      this.value = this.valueInput.color;
      this.UpdateProperty();
    });
    
    //Update the value every frame in the inspector
    Engine.scene.onBeforeRenderObservable.add(() =>
    {
      if (this.valueInput.color != this.value)
      {
        SceneManager.MarkSceneAsDirty();
        this.valueInput.color = this.value;
      }
        
    });
    */
  }

  TryDisposeColorPicker () 
  {
    if (this.currentPicker)
      {
        this.currentPicker.dispose();
        this.currentPicker = null;
        return;
      }
  }

}

                    [BoolInspector] => class BoolInspector extends PropertyInspector
{
  Init()
  {
    super.Init();
    
    this.valueInput = new BABYLON.GUI.Checkbox("Checkbox");
    this.valueInput.paddingLeftInPixels = 2;
    this.valueInput.paddingRightInPixels = 2;
    this.valueInput.margin = "2px";
    this.valueInput.width = "15px";
    this.valueInput.height = "10px";
    this.valueInput.color = "white";
    
    this.valueInput.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.addControl(this.valueInput);
    
    this.valueInput.isChecked = this.value;
    this.valueInput.onIsCheckedChangedObservable.add(() => {
      this.value = this.valueInput.isChecked;
      this.UpdateProperty();
    });
    
    //Update the value every frame in the inspector
    Engine.scene.onBeforeRenderObservable.add(() =>
    {
      if (this.valueInput.isChecked != this.value)
      {
        SceneManager.MarkSceneAsDirty();
        this.valueInput.isChecked = this.value;
      }
        
    });
    
  }

  
}
                    [Vector3Inspector] => //script:Vector3Inspector
class Vector3Inspector extends PropertyInspector
{
  Init ()
  {
    super.Init();
     Serializer.GetPropValues(this.value, true).forEach((value: any, key: string) => {
       
       this.addControl(PropertyInspector.Create(this.value, key, value));
     });   
  }

  Update()
  {
    
  }

}

                    [Dropdown] => class Dropdown extends BABYLON.GUI.StackPanel
{
  public isSearchable : boolean;
  public maxOptions : number;
  public onSelect : BABYLON.Observable<string>;

  options : string[] = new Array();
  searchInput : BABYLON.GUI.InputText;

  optionStack : BABYLON.GUI.StackPanel;

  filteredOptions : string[] = new Array();

 constructor (maxOptions : number = -1)
 {
   super();
   this.maxOptions = maxOptions;
   this.onSelect = new BABYLON.Observable();
    Engine.editorScene.onPointerObservable.add((pointerInfo) => {
      if (pointerInfo.type == BABYLON.PointerEventTypes.POINTERUP) {
        this.dispose();
      }
    });
 }

 public SetOptions (options: string[])
 {
    this.options = options;
    this.Refresh();
 }

 Refresh ()
 {
    this.searchInput = new EditorInputText();
    this.addControl(this.searchInput);
    this.searchInput.text = "";
    
    this.searchInput.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_CENTER;
    this.searchInput.onTextChangedObservable.add((ev) => {
      this.RefreshOptions();
    });

    this.searchInput.onKeyboardEventProcessedObservable.add((ev) =>
    {
      if (ev.keyCode == KeyCode.Enter)
      {
        if (this.filteredOptions.length > 0)
        {
          this.onSelect.notifyObservers(this.filteredOptions[0]);
        }
      }
    });
    this.RefreshOptions();
    //We have to wait one frame or else we get error messages?
    this.Focus();
 }

 async Focus ()
 {
    await null;
    UnionEditor.editorUI.moveFocusToControl(this.searchInput);
 }

 RefreshOptions () 
 {
   this.optionStack?.dispose();

   this.optionStack = new BABYLON.GUI.StackPanel;
   this.addControl(this.optionStack);
   var filter = "";
   if (this.isSearchable)
   {
     filter = this.searchInput.text;
   }

   var i = 0;
   this.filteredOptions = new Array();
   this.options.forEach((option : string) => {
    if (option.toLowerCase().includes(filter.toLowerCase()))
    {
      if (i != -1 && i >= this.maxOptions)
      {
        return;
      }
      i++;
      this.filteredOptions.push(option);
      
    }
   });

    this.filteredOptions.forEach((option : string) => {
    var scriptButton = BABYLON.GUI.Button.CreateSimpleButton("", option);
        scriptButton.width = "150px";
        scriptButton.height = "20px";
        scriptButton.color = UnionEditor.style.darkLineColor.toHexString();
        scriptButton.textBlock.color = UnionEditor.style.textColor.toHexString();
        scriptButton.onPointerClickObservable.add(() =>
        {
          this.onSelect.notifyObservers(option);
        });
        this.optionStack.addControl(scriptButton);
    });
 }
}

                    [EditorInputText] => class EditorInputText extends BABYLON.GUI.InputText {

  constructor ()
  {
    super("EditorInputText");
    this.style = UnionEditor.fontStyle;
    this.paddingLeftInPixels = 2;
    this.paddingRightInPixels = 2;
    this.margin = "2px";
    this.width = "150px"
    this.height = "20px";
    this.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.color = "white";
  }
}

                    [Material] => class Material {

  constructor(name: string) {
    this.shader = new BABYLON.StandardMaterial(name, Engine.scene);
  }

  shader: BABYLON.StandardMaterial;

  @SerializeField
  mainColor: Color = new Color();

  @SerializeField
  mainTexture: string = "";
  _lastTexture: string = "";

  @SerializeField
  alpha : number = 1;

  @SerializeField
  normalTexture: string = "";
  _lastNormalTexture: string = "";

  @SerializeField uScale: number = 1;
  @SerializeField vScale: number = 1;

  @SerializeField 
  specularColor : Color = new Color(0,0,0);

  @SerializeField
  specularPower : number = 0;

  @SerializeField
  roughness : number = 0;

  private Update() {
    Color.UtoB(this.mainColor, this.shader.diffuseColor);
    Color.UtoB(this.specularColor, this.shader.specularColor);

    this.shader.specularPower = this.specularPower;
    this.shader.alpha = this.alpha;
    this.shader.roughness = this.roughness;

    if (this.mainTexture && this.mainTexture != this._lastTexture) {
      this.shader.diffuseTexture = TextureSystem.GetSprite(this.mainTexture);
      this._lastTexture = this.mainTexture;
    }
    if (this.normalTexture && this.normalTexture != this._lastNormalTexture) {
      this.shader.bumpTexture = TextureSystem.GetSprite(this.normalTexture);
      this._lastNormalTexture = this.normalTexture;
    }

    var t = this.shader.diffuseTexture as BABYLON.Texture;

    if (t) {
      t.uScale = this.uScale;
      t.vScale = this.vScale;
    }
  }


  // #region static
  public static Get(name: string): Material {
    //Not in array or null?
    if (!this.nameToMat.has(name) || !this.nameToMat.get(name)) {
      //If no name is added, we just use default
      if (name) {
        var matJson = getMaterial(name);
      }

      var material: Material = null;
      //If we we used a non-existent material matJson would not be there.
      if (matJson) {
        material = Serializer.FromJSON(matJson, false);
      }

      if (!material) {
        material = new Material(name);
      }

      this.nameToMat.set(name, material);
    }

    return this.nameToMat.get(name);
  }


  public static SaveToJSON(name: string) {
    //Not in array or null?
    if (!this.nameToMat.has(name) || !this.nameToMat.get(name)) {
      Debug.Log("Error saving material " + name + ". This material does not exist!");
      return;
    }
    var matJson = Serializer.ToJSON(this.nameToMat.get(name));
    saveMaterial(name, matJson);
  }

  public static nameToMat: Map<string, Material>;

  public static Init() {
    this.nameToMat = new Map<string, Material>();

  }

  public static Update() {
    //We run an update method on all active materials 
    this.nameToMat.forEach((value: Material, key: string) => {
      value.Update();
    });
  }
  // #endregion
}

                    [MaterialInspector] => class MaterialInspector extends BABYLON.GUI.StackPanel {

  material : Material;

  propertiesPanel : BABYLON.GUI.StackPanel;

  materialName : string;

 constructor(materialName : string) {
    super()
    
    this.materialName = materialName;

    var material = Material.Get(materialName);

    this.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    
    this.material = material;
    
    this.propertiesPanel = new BABYLON.GUI.StackPanel();
    this.propertiesPanel.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
    this.propertiesPanel.isVisible = true;
    
    this.addControl(this.propertiesPanel);  

    Serializer.GetPropValues(material, false).forEach((value: any, key: string) => {
      var propInspector: PropertyInspector = PropertyInspector.Create(material, key, value);      
      this.propertiesPanel.addControl(propInspector);
      propInspector.onValueChanged.add((ev) => {
        Material.SaveToJSON(this.materialName);
      });
    });
   
 }

 
}

                    [ModelViewer] => class ModelViewer extends BABYLON.GUI.StackPanel {

  img: BABYLON.GUI.Image;

  valueInput: BABYLON.GUI.InputText;

  scene: BABYLON.Scene;
  cam: BABYLON.ArcRotateCamera;

  isValidFile: boolean = false;
  url: string;

  viewedObject: BABYLON.TransformNode;

  fileType: FileType = FileType.Texture;

  constructor() {
    super()

    this.valueInput = new BABYLON.GUI.InputText();
    this.valueInput.style = UnionEditor.fontStyle;
    this.valueInput.paddingLeftInPixels = 2;
    this.valueInput.paddingRightInPixels = 2;
    this.valueInput.margin = "2px";
    this.valueInput.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_CENTER;
    this.valueInput.width = "100px";
    this.valueInput.height = "40px";
    this.valueInput.color = UnionEditor.style.textColor.toHexString();
    this.valueInput.paddingTop = 10;
    this.valueInput.paddingBottom = 10;

    this.addControl(this.valueInput);

    this.RecreateTextureImage();

    var downloadButton = UnionEditor.CreateButton("Download");
    downloadButton.paddingTopInPixels = 10;
    downloadButton.heightInPixels = 30;
    downloadButton.isVisible = false;
    this.addControl(downloadButton);

    downloadButton.onPointerClickObservable.add(ev => {
      if (this.isValidFile) {
        window.open(this.url);
      }
    });

    this.onDisposeObservable.add(e => {
      this.OnDispose();
    });

    this.scene = new BABYLON.Scene(Engine.current);
    this.scene.autoClear = false;
    this.scene.clearColor = new BABYLON.Color4(1, 1, 1, 1);

    this.cam = new BABYLON.ArcRotateCamera("ArcRotateCamera", 0, 0, 10, new BABYLON.Vector3(0, 0, 0), this.scene);
    this.cam.position = new BABYLON.Vector3(0, 0, -5);


    this.cam.attachControl(Engine.canvas, true);
    this.cam.wheelPrecision = 10;
    this.scene.activeCameras.push(this.cam);

    var background = BABYLON.Mesh.CreatePlane("Background", 100000, this.scene);
    background.rotate(new BABYLON.Vector3(0, 0, 1), Math.PI * 0.5);
    background.position.z = 500;
    var m = new BABYLON.StandardMaterial("BackgroundMat", this.scene);
    m.disableLighting = true;
    background.material = m;
    //Parented so it follows when the camera zooms/rotates
    background.setParent(this.cam);


    var light = new BABYLON.DirectionalLight("light", new BABYLON.Vector3(0, 1, 0), this.scene);
    light.intensity = .7;

    //Render 
    Engine.editorScene.onAfterRenderObservable.add((ev) => {
      if (this.fileType == FileType.Model) {
        light.setDirectionToTarget(background.absolutePosition);

        //This is so we can use pixel coordinates instead of the ratio
        //That way, the viewport displays on top of our GUI
        var posX = 1 - 175 / Engine.canvas.width;
        var width = 150 / Engine.canvas.width;
        var posY = 158 / Engine.canvas.height;
        var height = 150 / Engine.canvas.height;

        this.cam.viewport = new BABYLON.Viewport(posX, posY, width, height);
        this.scene.render();
      }
    });

    this.viewedObject = new BABYLON.TransformNode("ViewedObject", this.scene);


    this.valueInput.onBlurObservable.add(e => {
      this.viewedObject?.dispose(false, true);
      this.img.widthInPixels = 0;
      downloadButton.isVisible = false;

      this.isValidFile = false;
      var fileName = this.valueInput.text;
      this.url = "";
      if (fileName.endsWith(".obj")) {
        this.url = getModel(fileName);
        this.fileType = FileType.Model;

        //Load the model
        var fullName = Engine.getFullName(fileName, FileType.Model);
        BABYLON.SceneLoader.ImportMesh("", Engine.getUrl(), fullName, this.scene, (meshes) => {
          meshes.forEach(m => {
            m.setParent(this.viewedObject);
          });
          this.viewedObject.scaling = new BABYLON.Vector3(0.1, 0.1, 0.1);
        });
      } else if (fileName.endsWith(".png") || fileName.endsWith(".jpg")) {
        this.url = getTexture(fileName);

        this.fileType = FileType.Texture;
        //Load the texture
        var fullName = Engine.getFullName(fileName, FileType.Texture);
        this.img.source = this.url;
        this.img.widthInPixels = 150;
      } else {
        Debug.Log("Unrecognized file-format.");
        return;
      }
      if (!this.url) {
        Debug.Log("Unrecognized filename");
        return;
      }
      downloadButton.isVisible = true;
      this.isValidFile = true;
    });
  }

  RecreateTextureImage () 
  {
    this.img?.dispose();
    this.img = new BABYLON.GUI.Image("TextureViewer");
    this.img.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_CENTER;
    this.img.width = "150px";
    this.img.height = "150px";
    this.img.stretch = BABYLON.GUI.Image.STRETCH_UNIFORM;
    this.addControl(this.img);
  }

  OnDispose() {
    this.onDisposeObservable.removeCallback(this.OnDispose);
    this.scene.dispose();
  }

}

                    [Color] => class Color  {

    constructor (r :number = 1, g :number = 1, b : number = 1)
    {
        this.r = r;
        this.g = g;
        this.b = b;
    }

    @SerializeField
    public r : number;
    @SerializeField
    public g : number;
    @SerializeField
    public b : number;


    public static UtoB (u: Color, b : BABYLON.Color3) : BABYLON.Color3
    {
        b.r = u.r;
        b.g = u.g;
        b.b = u.b
        return b;
    }

    public static BtoU  (b : BABYLON.Color3, u : Color) : Color
    {
        u.r = b.r;
        u.g = b.g;
        u.b = b.b;
        return u;
    }
}

                    [PrefabInspector] => class PrefabInspector extends BABYLON.GUI.StackPanel {

   propertiesPanel: BABYLON.GUI.StackPanel;

   name: string;

   constructor(prefabName: string) {
      super()

      this.name = prefabName;

      var prefab = Prefab.Get(prefabName);

      this.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;

      var nameField = new BABYLON.GUI.TextBlock("", prefabName);

      nameField.paddingLeft = "5px";
      nameField.style = UnionEditor.fontStyleBold;
      nameField.color =  UnionEditor.style.textColor.toHexString();
      nameField.width = "160px";
      nameField.heightInPixels = 25;
      nameField.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_CENTER;
      nameField.verticalAlignment = BABYLON.GUI.Control.VERTICAL_ALIGNMENT_TOP;

      
      this.heightInPixels = 400;
      this.paddingTopInPixels = 3;
      this.paddingBottomInPixels = 5;

      this.propertiesPanel = new BABYLON.GUI.StackPanel();
      this.propertiesPanel.horizontalAlignment = BABYLON.GUI.Control.HORIZONTAL_ALIGNMENT_LEFT;
      this.propertiesPanel.isVisible = true;
      
      this.addControl(this.propertiesPanel);

      this.propertiesPanel.addControl(nameField);

      var createPrefabButton = UnionEditor.CreateButton("Create");
      createPrefabButton.widthInPixels = 100;
      this.propertiesPanel.addControl(createPrefabButton);
      createPrefabButton.onPointerClickObservable.add((ev) => {
         Prefab.Create(prefabName);
      });

      /*
      var savePrefabButton = UnionEditor.CreateButton("Save Selection");
      this.propertiesPanel.addControl(savePrefabButton);
      savePrefabButton.onPointerClickObservable.add((ev) => {
         if (!EditorSelection.currentSelectedGameObject)
         {
            Debug.Log("Select a GameObject to save");
         } else {
            var prefabJson = Serializer.ToJSON(EditorSelection.currentSelectedGameObject);
            savePrefab(prefabName, prefabJson);
         }
      });
      */

      var openPrefabButton = UnionEditor.CreateButton("Open Prefab");
      this.propertiesPanel.addControl(openPrefabButton);
      openPrefabButton.onPointerClickObservable.add((ev) => {
         if (SceneManager.GetActiveScene().isDirty)
         {
            alert("Save scene first you dumb dumb!");
         } else {
            SceneManager.LoadPrefab(prefabName);
         }
      });
   }


}

                    [Prefab] => class Prefab  {

  public static Get (name : string) : string
  {
    return getPrefab(name);
  }

  public static SimpleName (name : string)
  {
    return name.slice(0, -7);
  }

  public static Create (name : string, goParent : GameObject = null) : GameObject
  {
    var prefabJson = getPrefab(name);

    var parent = goParent ? goParent  : new GameObject();
    parent.name = this.SimpleName(name) + " (Clone)";
    parent.prefabParent = parent;
    parent.prefabName = name;
    var clone : Scene = Serializer.FromJSON(prefabJson);
    clone.rootObjs.forEach((go : GameObject) => {
      go.transform.SetParent(parent.transform, false);
      go.prefabParent = parent;
    });

    return parent;
  }
}

                    [Shadows] => class Shadows {

  private static shadowGenerators : BABYLON.ShadowGenerator[] = new Array();

  private static casters : BABYLON.Mesh[] = new Array();

  public static RemoveCastingMesh (m : BABYLON.Mesh)
  {    
    const index = this.casters.indexOf(m);
    if (index > -1) {
      var removedMesh = this.casters.splice(index, 1)[0];
      this.shadowGenerators.forEach((gen) => {
        gen.removeShadowCaster(removedMesh);
      });
    }
  }

  public static AddCastingMesh(m : BABYLON.Mesh)
  {
    const index = this.casters.indexOf(m);
    if (index == -1) {
      this.casters.push(m);
      this.shadowGenerators.forEach((gen) => {
        gen.addShadowCaster(m, true);
      });
    }
  }

  public static AddLight (l : Light)
  {
    var generator = new BABYLON.ShadowGenerator(1024, l.light, true);
    generator.useExponentialShadowMap = true;  
    this.shadowGenerators.push(generator);
    //We add all previous casters to our new light
    this.casters.forEach((m : BABYLON.Mesh) => {
      generator.addShadowCaster(m, true);
    });
  }

  public static RemoveLight (l : Light)
  {
    let index = -1;
    this.shadowGenerators.forEach((gen) => {
      if (gen.getLight() == l.light)
      {
        index = this.shadowGenerators.indexOf(gen);
        gen.dispose();    
      }
    });

    //Remove the generator from our array
    if (index > -1) {
      this.shadowGenerators.splice(index, 1);
    }
  }

  //Start is called before the first frame update
  static Init() {
    
    //Create main light
    var light1 = new BABYLON.HemisphericLight("light", new BABYLON.Vector3(0, 1, 0), Engine.scene);
    light1.intensity = .3;
    
  }
}

                    [Cursor] => class Cursor {

  static Init ()
  {
     
    Engine.editorScene.onPrePointerObservable.add((ev) => {
      if (!Engine.current.isPointerLock && Cursor.lockState == CursorLockMode.Locked)
      {
        Cursor.lockState = CursorLockMode.None;
      }

      //When the cursor is unlocked somehow, we reset it to none here      
      if (Cursor.lockState == CursorLockMode.Locked)
      {
        ev.skipOnPointerObservable = true;
      }
    });
  }


  static get lockState (): CursorLockMode{
    return this._lockState;
  }

  static set lockState(value:CursorLockMode)
  {
    Cursor._lockState = value;
    switch(value)
    {
      case CursorLockMode.Locked:
        Engine.current.enterPointerlock();
      break;
      case CursorLockMode.None:
        Engine.current.exitPointerlock();
      break;
      case CursorLockMode.Confined:
        Debug.Log("Confined lockmode is currently not supported")
    }
  }

  private static _lockState : CursorLockMode;
}

enum CursorLockMode
{
  Locked,
  Confined,
  None
}
                )

            [model] => stdClass Object
                (
                    [asteroid.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.asteroid.obj
                        )

                    [ship.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.ship.obj
                        )

                    [ball.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.ball.obj
                        )

                    [virus.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.virus.obj
                        )

                    [crate.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.crate.obj
                        )

                    [fence.obj] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.fence.obj
                        )

                )

            [texture] => stdClass Object
                (
                    [laser.png] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.laser.png
                        )

                    [nebula.jpg] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.nebula.jpg
                        )

                    [asteroid.jpg] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.asteroid.jpg
                        )

                    [ship.jpg] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.ship.jpg
                        )

                    [Grass.jpg] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.Grass.jpg
                        )

                    [Wood.png] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.Wood.png
                        )

                    [synty.png] => stdClass Object
                        (
                            [uri] => https://s3.us-west-1.amazonaws.com/test.pixelpad.io/__PIXELPAD_ASSET__.4892.210651.synty.png
                        )

                )

            [sound] => stdClass Object
                (
                )

            [library] => stdClass Object
                (
                )

            [scene] => stdClass Object
                (
                    [MainScene.scn] => {
   "t":"Scene",
   "rootObjs":[
      
   ]
}
                    [TEST.scn] => {
   "t":"Scene",
   "rootObjs":[
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":2.1333043575286865,
                  "y":0,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "uniqueId":797,
               "ser_children":[
                  
               ]
            },
            {
               "t":"Renderer",
               "meshName":"box",
               "materialName":"",
               "scale":1
            },
            {
               "t":"BoxCollider",
               "_isTrigger":false,
               "_layer":1,
               "_layerMask":1,
               "_size":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "_center":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               }
            },
            {
               "t":"Rigidbody",
               "_mass":10,
               "_isKinematic":false
            }
         ],
         "prefabName":null
      }
   ]
}
                )

            [material] => stdClass Object
                (
                    [Red.mat] => {"t":"Material","mainColor":{"t":"Color","r":1,"g":0,"b":0},"mainTexture":"","alpha":1,"normalTexture":"","uScale":1,"vScale":1,"specularColor":{"t":"Color","r":0,"g":0,"b":0},"specularPower":0,"roughness":0}
                    [Asteroid.mat] => {"t":"Material","mainColor":{"t":"Color","r":1,"g":1,"b":1},"mainTexture":"asteroid.jpg","alpha":1,"normalTexture":"","uScale":1,"vScale":1,"specularColor":{"t":"Color","r":0,"g":0,"b":0},"specularPower":0,"roughness":0}
                    [Grass.mat] => {"t":"Material","mainColor":{"t":"Color","r":1,"g":1,"b":1},"mainTexture":"Grass.jpg","alpha":1,"normalTexture":"","uScale":5,"vScale":5,"specularColor":{"t":"Color","r":0,"g":0,"b":0},"specularPower":0,"roughness":0}
                    [Yellow.mat] => {"t":"Material","mainColor":{"t":"Color","r":1,"g":0.792158236747296,"b":0},"mainTexture":"","alpha":1,"normalTexture":"","uScale":1,"vScale":1,"specularColor":{"t":"Color","r":0,"g":0,"b":0},"specularPower":0,"roughness":0}
                    [Synty.mat] => {"t":"Material","mainColor":{"t":"Color","r":1,"g":1,"b":1},"mainTexture":"synty.png","alpha":1,"normalTexture":"","uScale":1,"vScale":1,"specularColor":{"t":"Color","r":0,"g":0,"b":0},"specularPower":9.260000000000003,"roughness":20.02000000000001}
                    [Flip.mat] => //material: Flip.mat


                )

            [prefab] => stdClass Object
                (
                    [Test.prefab] => {
   "t":"Scene",
   "rootObjs":[
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":0.11797763309128564,
                  "y":-2.3257447407301215,
                  "z":1
               },
               "uniqueId":1721,
               "ser_children":[
                  
               ]
            },
            {
               "t":"Renderer",
               "meshName":"box",
               "materialName":"",
               "scale":1,
               "castShadows":true,
               "receiveShadows":false
            },
            {
               "t":"BoxCollider",
               "_isTrigger":false,
               "_layer":1,
               "_layerMask":1,
               "_size":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "_center":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               }
            },
            {
               "t":"Rigidbody",
               "_mass":10,
               "_isKinematic":false
            }
         ],
         "prefabName":null
      }
   ]
}
                    [Robot.prefab] => {
   "t":"Scene",
   "rootObjs":[
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "uniqueId":9638,
               "ser_children":[
                  
               ]
            }
         ],
         "prefabName":null
      },
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":0.7628781554881394,
                  "y":0,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":-0.35345975736876584,
                  "y":1,
                  "z":1
               },
               "uniqueId":9639,
               "ser_children":[
                  
               ]
            },
            {
               "t":"Renderer",
               "meshName":"box",
               "materialName":"",
               "scale":1,
               "castShadows":true,
               "receiveShadows":false
            },
            {
               "t":"BoxCollider",
               "_isTrigger":false,
               "_layer":1,
               "_layerMask":1,
               "_size":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "_center":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               }
            },
            {
               "t":"Rigidbody",
               "_mass":10,
               "_isKinematic":false
            }
         ],
         "prefabName":null
      },
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":-0.35345975736876584,
                  "y":1,
                  "z":1
               },
               "uniqueId":9648,
               "ser_children":[
                  
               ]
            },
            {
               "t":"Renderer",
               "meshName":"box",
               "materialName":"",
               "scale":1,
               "castShadows":true,
               "receiveShadows":false
            },
            {
               "t":"BoxCollider",
               "_isTrigger":false,
               "_layer":1,
               "_layerMask":1,
               "_size":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "_center":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               }
            },
            {
               "t":"Rigidbody",
               "_mass":10,
               "_isKinematic":false
            }
         ],
         "prefabName":null
      },
      {
         "t":"GameObject",
         "name":"GameObject",
         "components":[
            {
               "t":"Transform",
               "_position":
               {
                  "t":"Vector3",
                  "x":0.36953156155958977,
                  "y":0.948546214173521,
                  "z":0
               },
               "_eulerAngles":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               },
               "_scale":
               {
                  "t":"Vector3",
                  "x":-0.35345975736876584,
                  "y":1,
                  "z":1
               },
               "uniqueId":9657,
               "ser_children":[
                  
               ]
            },
            {
               "t":"Renderer",
               "meshName":"box",
               "materialName":"",
               "scale":1,
               "castShadows":true,
               "receiveShadows":false
            },
            {
               "t":"BoxCollider",
               "_isTrigger":false,
               "_layer":1,
               "_layerMask":1,
               "_size":
               {
                  "t":"Vector3",
                  "x":1,
                  "y":1,
                  "z":1
               },
               "_center":
               {
                  "t":"Vector3",
                  "x":0,
                  "y":0,
                  "z":0
               }
            },
            {
               "t":"MainGame"
            },
            {
               "t":"Rigidbody",
               "_mass":10,
               "_isKinematic":false
            }
         ],
         "prefabName":null
      }
   ]
}
                )

        )

)
