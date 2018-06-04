
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

class SocketClient {

  private commands = {};
  private socket: WebSocket;
  private config = {
    open: function() { }
  }

  constructor(url: string, settings?) {
    
  }

}
