
interface SocketClientConfig { 
  open: () => void; 
  close: () => void; 
  message: () => void; 
  options: {}, 
  events: {} 
} 
 
const DefaultSocketClientConfig: SocketClientConfig = { 
  open: () => {},
  close: () => {},
  message: () => {},
  options: {},
  events: {}
};

interface Message {
  command: string;
  data: any;
  sender: any;
}

type Command = (params, sender) => void;

interface Commands {
  [command: string]: Command;
}
 
class SocketClient { 
 
  private commands: Commands = {};
  private socket: WebSocket & { user?: string }; 
  private config: SocketClientConfig;
 
  constructor(public url: string, settings?: Partial<SocketClientConfig>) { 
     this.config = { ...DefaultSocketClientConfig, ...settings };

  }
  
  public on(command: string, callback: Command) {
    this.commands[command] = callback;
  }

  public trigger(command: string, params: any, sender?: any) {
    if (this.commands.hasOwnProperty(command)) {
      this.commands[command](params, sender);
    }
  }

  public quit() {
    if (!!this.socket) {
      this.socket.close();
      this.socket = null;
    }
  }
  
  public reconnect() {
    this.quit();
    this.listen();
  }

  private wrapCommand<T>(command: string, data: T, broadcast: boolean): string;
  private wrapCommand(command: string, data: any, broadcast: boolean): string {
    let response = {
      command,
      data,
      broadcast,
      sender: this.socket.user
    };

    return JSON.stringify(response);
  }

  public emit(command: string, data: any) {
    if (!this.socket) {
      return;
    }

    let message = this.wrapCommand(command, data, false);

    try {
      this.socket.send(message);
    } catch (err) {
      console.error(err);
    }
  }

  public push(command: string, data: any, to: string) {
    if (!this.socket) {
      return;
    }

    let message = this.wrapCommand(command, { data, to }, false);

    try {
      this.socket.send(message);
    } catch (err) {
      console.error(err);
    }
  }

  public broadcast(command: string, data: any) {
    if (!this.socket) {
      return;
    }

    let message = this.wrapCommand(command, data, true);

    try {
      this.socket.send(message);
    } catch (err) {
      console.error(err);
    }
  }

  public listen() {
    try {
      this.socket = new WebSocket(this.url);

      this.socket.onopen = (event) => {
        this.trigger("open", event);
      };

      this.socket.onmessage = (event) => {
        let message: Message = JSON.parse(event.data);

        switch (message.command) {
          case "connect":
            this.socket.user = message.data;
            break;

          case "disconnect":
          case "close":
            this.socket.user = null;
            break;
        }

        this.trigger(message.command, message.data, message.sender);
      };

      this.socket.onclose = (event) => {
        this.trigger("close", event);
      };
    } catch (err) {
      console.error(err);
    }
  }

}
